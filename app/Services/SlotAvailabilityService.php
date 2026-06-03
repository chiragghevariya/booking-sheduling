<?php

namespace App\Services;

use App\Models\Availability;
use App\Models\AvailabilityException;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Computes bookable slots for a provider/service combination over a date range.
 *
 * The algorithm, per day:
 *   1. Start from the provider's weekly availability windows for that weekday.
 *   2. If a "custom" exception exists on that date, replace the weekly windows
 *      with the custom windows (an explicit override).
 *   3. Subtract any "blocked" exceptions on that date (whole-day or partial).
 *   4. Subtract any pending or approved bookings overlapping that date,
 *      expanded by the service's buffer on both sides.
 *   5. Slice each remaining window into consecutive slots of the service's
 *      duration. Within a single uninterrupted window we step exactly one
 *      duration at a time — buffer is only meaningful between bookings.
 *   6. Drop slots in the past (relative to "now").
 */
class SlotAvailabilityService
{
    public function __construct(
        private readonly CarbonInterface $now = new CarbonImmutable(),
    ) {
    }

    /**
     * @param  array{ignoreBookings?: bool, statuses?: list<string>}  $opts
     *   - ignoreBookings (default false): when true, returns slots based on weekly
     *     availability + exceptions only, ignoring existing bookings entirely.
     *     Used by BookingService::assertWithinAvailability to validate that a
     *     requested time is inside working hours, without considering soft-held
     *     pending bookings (which are sorted out at approval time).
     *   - statuses (default ['pending','approved']): which booking statuses to
     *     subtract when ignoreBookings is false.
     *
     * @return array<int, array{starts_at: string, ends_at: string}>
     */
    public function slotsFor(User $provider, Service $service, CarbonInterface $from, CarbonInterface $to, array $opts = []): array
    {
        $duration = $service->duration_minutes;
        $buffer = $service->buffer_minutes;
        $ignoreBookings = $opts['ignoreBookings'] ?? false;
        $statuses = $opts['statuses'] ?? [Booking::STATUS_PENDING, Booking::STATUS_APPROVED];

        $weekly = $provider->availabilities()
            ->where('is_active', true)
            ->get()
            ->groupBy('day_of_week');

        $exceptions = $provider->availabilityExceptions()
            ->whereBetween('date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get()
            ->groupBy(fn (AvailabilityException $e) => $e->date->toDateString());

        $bookings = $ignoreBookings
            ? collect()
            : Booking::query()
                ->where('provider_id', $provider->id)
                ->whereIn('status', $statuses)
                ->where('starts_at', '<', $to->copy()->endOfDay())
                ->where('ends_at', '>', $from->copy()->startOfDay())
                ->get();

        $slots = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $dayStr = $cursor->toDateString();
            $dayExceptions = $exceptions->get($dayStr, collect());

            $windows = $this->windowsForDay($cursor, $dayExceptions, $weekly);
            $windows = $this->subtractBlockedExceptions($windows, $dayExceptions);
            if (! $ignoreBookings) {
                $windows = $this->subtractBookings($windows, $cursor, $bookings, $buffer);
            }

            foreach ($windows as [$startMin, $endMin]) {
                for ($t = $startMin; $t + $duration <= $endMin; $t += $duration) {
                    $start = $cursor->copy()->addMinutes($t);
                    $finish = $cursor->copy()->addMinutes($t + $duration);

                    if ($start->lte($this->now)) {
                        continue; // skip past slots
                    }

                    $slots[] = [
                        'starts_at' => $start->toIso8601String(),
                        'ends_at' => $finish->toIso8601String(),
                    ];
                }
            }

            $cursor = $cursor->addDay();
        }

        return $slots;
    }

    /**
     * @param  Collection<int, AvailabilityException>  $dayExceptions
     * @param  Collection<int, Collection<int, Availability>>  $weekly
     * @return array<int, array{0:int,1:int}>  windows in minutes-from-midnight
     */
    private function windowsForDay(CarbonInterface $date, Collection $dayExceptions, Collection $weekly): array
    {
        // Whole-day block short-circuits.
        $fullBlock = $dayExceptions->first(
            fn (AvailabilityException $e) => $e->type === 'blocked' && ! $e->start_time && ! $e->end_time,
        );
        if ($fullBlock) {
            return [];
        }

        // "custom" exceptions override the weekly schedule entirely for that date.
        $customs = $dayExceptions->where('type', 'custom');
        if ($customs->isNotEmpty()) {
            $windows = [];
            foreach ($customs as $c) {
                if ($c->start_time && $c->end_time) {
                    $windows[] = [$this->toMinutes($c->start_time), $this->toMinutes($c->end_time)];
                }
            }

            return $this->normalize($windows);
        }

        $rows = $weekly->get($date->dayOfWeek, collect());
        $windows = $rows->map(fn (Availability $a) => [
            $this->toMinutes($a->start_time),
            $this->toMinutes($a->end_time),
        ])->all();

        return $this->normalize($windows);
    }

    /**
     * @param  array<int, array{0:int,1:int}>  $windows
     * @param  Collection<int, AvailabilityException>  $dayExceptions
     * @return array<int, array{0:int,1:int}>
     */
    private function subtractBlockedExceptions(array $windows, Collection $dayExceptions): array
    {
        $partialBlocks = $dayExceptions->filter(
            fn (AvailabilityException $e) => $e->type === 'blocked' && $e->start_time && $e->end_time,
        );

        foreach ($partialBlocks as $b) {
            $windows = $this->subtract($windows, $this->toMinutes($b->start_time), $this->toMinutes($b->end_time));
        }

        return $windows;
    }

    /**
     * @param  array<int, array{0:int,1:int}>  $windows
     * @param  Collection<int, Booking>  $bookings
     * @return array<int, array{0:int,1:int}>
     */
    private function subtractBookings(array $windows, CarbonInterface $date, Collection $bookings, int $buffer): array
    {
        $dayStart = $date->copy()->startOfDay();
        $dayEnd = $date->copy()->endOfDay();

        foreach ($bookings as $b) {
            // Skip bookings that don't intersect this day.
            if ($b->ends_at->lte($dayStart) || $b->starts_at->gte($dayEnd)) {
                continue;
            }

            $blockStart = max(0, $dayStart->diffInMinutes($b->starts_at->copy()->subMinutes($buffer), false));
            $blockEnd = min(24 * 60, $dayStart->diffInMinutes($b->ends_at->copy()->addMinutes($buffer), false));

            if ($blockEnd > $blockStart) {
                $windows = $this->subtract($windows, $blockStart, $blockEnd);
            }
        }

        return $windows;
    }

    /**
     * Subtract the interval [from, to] from a list of windows.
     *
     * @param  array<int, array{0:int,1:int}>  $windows
     * @return array<int, array{0:int,1:int}>
     */
    private function subtract(array $windows, int $from, int $to): array
    {
        $out = [];
        foreach ($windows as [$ws, $we]) {
            if ($to <= $ws || $from >= $we) {
                $out[] = [$ws, $we];
                continue;
            }
            if ($from > $ws) {
                $out[] = [$ws, $from];
            }
            if ($to < $we) {
                $out[] = [$to, $we];
            }
        }

        return $out;
    }

    /**
     * Sort and merge overlapping/adjacent windows.
     *
     * @param  array<int, array{0:int,1:int}>  $windows
     * @return array<int, array{0:int,1:int}>
     */
    private function normalize(array $windows): array
    {
        if (empty($windows)) {
            return [];
        }
        usort($windows, fn ($a, $b) => $a[0] <=> $b[0]);
        $merged = [$windows[0]];
        for ($i = 1, $n = count($windows); $i < $n; $i++) {
            [$ws, $we] = $windows[$i];
            $last = &$merged[count($merged) - 1];
            if ($ws <= $last[1]) {
                $last[1] = max($last[1], $we);
            } else {
                $merged[] = [$ws, $we];
            }
        }

        return $merged;
    }

    private function toMinutes(CarbonInterface|Carbon $time): int
    {
        return $time->hour * 60 + $time->minute;
    }
}
