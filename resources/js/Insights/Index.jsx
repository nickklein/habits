import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import HabitTile from '../partials/HabitTile';

export default function Index(props) {
    const {links} = usePage().props;
    const [habitsData, setHabitsData] = useState({});

    useEffect(() => {
        if (!props.habitUserIds) return;

        // Reset habits data
        setHabitsData({});

        // Fetch each habit independently
        props.habitUserIds.forEach(habitUserId => {
            fetch(route('api.habits.summary', {'habitUserId': habitUserId, 'page': 'insights'}))
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
    }, [props.habitUserIds]);

    return (
        <AuthenticatedLayout
            auth={props.auth}
            errors={props.errors}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Insights</h2>}
        >
            <Head title="Habits" />
            <div className={"max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8"}>
                {props.habitUserIds && props.habitUserIds.map(habitUserId => {
                    const habitInfo = habitsData[habitUserId] || { data: null, error: null, loading: true };

                    return (
                        <HabitTile
                            key={habitUserId}
                            habitUserId={habitUserId}
                            type='insights'
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
