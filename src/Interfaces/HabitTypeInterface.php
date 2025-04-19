<?php

namespace NickKlein\Habits\Interfaces;

use NickKlein\Habits\Models\HabitUser;

interface HabitTypeInterface
{
    public function formatValue(int $value): array;
    public function formatGoal(HabitUser $habitUser): array;
    public function getUnitLabel(): string;
    public function getUnitLabelFull(): string;
    public function formatDifference(int $value1, int $value2): string;
    public function formatStreakGoal(int $goalValue): string;
    public function calculatePercentageDifference(int $value1, int $value2): array;
    public function meetsGoal(int $value, int $goalValue): bool;
    public function recordValue(int $habitId, int $userId, int $value, string $timezone = 'UTC'): bool;
}
