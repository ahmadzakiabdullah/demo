<?php

namespace App\Support;

use App\Models\Athlete;
use App\Models\Event;
use App\Models\Medal;
use App\Models\Team;
use Illuminate\Support\Collection;

class MedalTallyAggregator
{
    /**
     * @return array{by_recipient: Collection, by_organization: Collection, by_country: Collection}
     */
    public function aggregate(Event $event, ?int $sportId = null): array
    {
        $medals = Medal::query()
            ->where('event_id', $event->id)
            ->when($sportId, fn ($query) => $query->where('sport_id', $sportId))
            ->with(['medalable', 'medalable.organization'])
            ->get();

        return [
            'by_recipient' => $this->tallyBy($medals, fn (Medal $medal) => $medal->medalable?->name ?? 'Unknown'),
            'by_organization' => $this->tallyBy($medals, fn (Medal $medal) => $this->organizationLabel($medal)),
            'by_country' => $this->tallyBy($medals, fn (Medal $medal) => $this->countryLabel($medal)),
        ];
    }

    /**
     * @param  Collection<int, Medal>  $medals
     */
    private function tallyBy(Collection $medals, callable $labelResolver): Collection
    {
        return $medals
            ->groupBy(fn (Medal $medal) => $labelResolver($medal))
            ->map(function (Collection $items, string $label) {
                return [
                    'label' => $label,
                    'gold' => $items->where('type', 'gold')->count(),
                    'silver' => $items->where('type', 'silver')->count(),
                    'bronze' => $items->where('type', 'bronze')->count(),
                    'total' => $items->count(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function organizationLabel(Medal $medal): string
    {
        $medalable = $medal->medalable;

        if ($medalable instanceof Team) {
            return $medalable->organization?->name ?? 'Unknown organization';
        }

        if ($medalable instanceof Athlete) {
            return $medalable->organization?->name ?? 'Unknown organization';
        }

        return 'Unknown organization';
    }

    private function countryLabel(Medal $medal): string
    {
        $medalable = $medal->medalable;

        if ($medalable instanceof Athlete) {
            return $medalable->nationality ?: 'Unknown country';
        }

        if ($medalable instanceof Team) {
            return $medalable->organization?->name ?? 'Unknown country';
        }

        return 'Unknown country';
    }
}