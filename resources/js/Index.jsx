import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import DailyCalendar from './partials/DailyCalendar';
import HabitTile from './partials/HabitTile';

export default function Index(props) {
    const {links} = usePage().props;
    const [selectedDate, setSelectedDate] = useState(props.todaysDate);

    return (
        <AuthenticatedLayout
            auth={props.auth}
            errors={props.errors}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Habits</h2>}
        >
            <Head title="Habits" />
            <div className={"max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8"}>
                <DailyCalendar onDateSelect={setSelectedDate} />
                {props.habitUserIds && props.habitUserIds.map((habitUserId, index) => (
                    <HabitTile 
                        key={habitUserId} 
                        habitUserId={habitUserId} 
                        selectedDate={selectedDate} 
                        type='home'
                        ajaxUrl={route('api.habits.summary', {'habitUserId': habitUserId, 'page': 'home'})+`?date=${selectedDate}`}
                    />
                ))}
            </div>
        </AuthenticatedLayout>
    )
}
