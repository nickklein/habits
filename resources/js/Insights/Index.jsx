import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import HabitTile from '../partials/HabitTile';

export default function Index(props) {
    const {links} = usePage().props;

    return (
        <AuthenticatedLayout
            auth={props.auth}
            errors={props.errors}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Insights</h2>}
        >
            <Head title="Habits" />
            <div className={"max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8"}>
                {props.habitUserIds && props.habitUserIds.map((habitUserId, index) => (
                    <HabitTile key={habitUserId} habitUserId={habitUserId} type='insights' />
                ))}
            </div>
        </AuthenticatedLayout>
    )
}
