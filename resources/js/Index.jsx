import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState, useEffect, useMemo } from 'react';
import DailyCalendar from './partials/DailyCalendar';
import HabitTile from './partials/HabitTile';

export default function Index(props) {
    const {links} = usePage().props;
    const [selectedDate, setSelectedDate] = useState(props.todaysDate);
    const [habitsData, setHabitsData] = useState({});

    useEffect(() => {
        if (!props.habitUserIds) return;

        // Reset habits data when date changes
        setHabitsData({});

        // Fetch each habit independently
        props.habitUserIds.forEach(habitUserId => {
            fetch(route('api.habits.summary', {'habitUserId': habitUserId, 'page': 'home'})+`?date=${selectedDate}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to load habit data');
                    }
                    return response.json();
                })
                .then(data => {
                    setHabitsData(prev => ({
                        ...prev,
                        [habitUserId]: { data, error: null, loading: false }
                    }));
                })
                .catch(error => {
                    setHabitsData(prev => ({
                        ...prev,
                        [habitUserId]: { data: null, error: error.message, loading: false }
                    }));
                });
        });
    }, [selectedDate, props.habitUserIds]);

    // Sort habit IDs based on loaded data
    const sortedHabitIds = useMemo(() => {
        if (!props.habitUserIds) return [];

        return [...props.habitUserIds].sort((a, b) => {
            const aData = habitsData[a];
            const bData = habitsData[b];

            // If data not loaded yet, keep original order
            if (!aData?.data || !bData?.data) return 0;

            const aGoalMet = aData.data.goal_met || false;
            const bGoalMet = bData.data.goal_met || false;

            const aHasIncompleteChildren = aData.data.children?.some(child => !child.goal_met) || false;
            const bHasIncompleteChildren = bData.data.children?.some(child => !child.goal_met) || false;

            const aCompleted = aGoalMet && !aHasIncompleteChildren;
            const bCompleted = bGoalMet && !bHasIncompleteChildren;

            if (aCompleted === bCompleted) return 0;
            return aCompleted ? 1 : -1;
        });
    }, [props.habitUserIds, habitsData]);

    return (
        <AuthenticatedLayout
            auth={props.auth}
            errors={props.errors}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Habits</h2>}
        >
            <Head title="Habits" />
            <div className={"max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8"}>
                <DailyCalendar onDateSelect={setSelectedDate} />
                {sortedHabitIds.map(habitUserId => {
                    const habitInfo = habitsData[habitUserId] || { data: null, error: null, loading: true };

                    return (
                        <HabitTile
                            key={habitUserId}
                            habitUserId={habitUserId}
                            selectedDate={selectedDate}
                            type='home'
                            habitData={habitInfo.data}
                            error={habitInfo.error}
                            loading={habitInfo.loading}
                        />
                    );
                })}
            </div>
        </AuthenticatedLayout>
    )
}
