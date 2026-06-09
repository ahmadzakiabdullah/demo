<?php

namespace App\Services;

use App\Enums\SportGender;
use App\Models\Athlete;
use App\Models\Event;
use App\Models\Official;
use App\Models\SportCategory;

class EligibilityService
{
    /**
     * @return list<string>
     */
    public function issues(Athlete $athlete, ?SportCategory $category, Event $event): array
    {
        $issues = [];

        if (! $athlete->medical_clearance) {
            $issues[] = 'Medical clearance is required.';
        }

        if ($category === null) {
            return $issues;
        }

        if ($athlete->gender !== null
            && ! in_array($category->gender, [SportGender::Open, SportGender::Mixed], true)
            && $athlete->gender !== $category->gender) {
            $issues[] = 'Gender does not match the selected category.';
        }

        $referenceDate = $event->starts_at ?? now();
        $age = $athlete->ageAt($referenceDate);

        if ($age !== null && $category->min_age !== null && $age < $category->min_age) {
            $issues[] = "Athlete age ({$age}) is below the category minimum ({$category->min_age}).";
        }

        if ($age !== null && $category->max_age !== null && $age > $category->max_age) {
            $issues[] = "Athlete age ({$age}) exceeds the category maximum ({$category->max_age}).";
        }

        if ($athlete->weight !== null && $category->min_weight !== null && $athlete->weight < $category->min_weight) {
            $issues[] = "Athlete weight ({$athlete->weight}kg) is below the category minimum ({$category->min_weight}kg).";
        }

        if ($athlete->weight !== null && $category->max_weight !== null && $athlete->weight > $category->max_weight) {
            $issues[] = "Athlete weight ({$athlete->weight}kg) exceeds the category maximum ({$category->max_weight}kg).";
        }

        return $issues;
    }

    public function isEligible(Athlete $athlete, ?SportCategory $category, Event $event): bool
    {
        return $this->issues($athlete, $category, $event) === [];
    }

    /**
     * @return list<string>
     */
    public function officialIssues(Official $official): array
    {
        $issues = [];

        if ($official->certification_expires_at !== null
            && $official->certification_expires_at->isPast()) {
            $issues[] = 'Certification has expired.';
        }

        return $issues;
    }

    public function isOfficialEligible(Official $official): bool
    {
        return $this->officialIssues($official) === [];
    }
}