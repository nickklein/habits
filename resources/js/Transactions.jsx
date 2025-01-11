import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage, Link } from '@inertiajs/react';
import { useState } from 'react';

export default function Transactions(props) {
    const {lists, anyHabitActive} = usePage().props;
    const [habits, setHabits] = useState(lists);

    // Define the function to handle the delete event
    function handleDelete(id) {
        axios.delete(route('habits.transactions.destroy', {id}))
        .then(response => {
            // Handle the response from the server
            if (response.status === 200) {
                // Remove the deleted habit from the list
                // setHabits(prevHabits => prevHabits.filter(habit => habit.id !== id));
                setHabits(prevHabits => ({
                    ...prevHabits,
                    data: prevHabits.data.filter(habit => habit.id !== id)
                }));                
            } else {
                console.error('Failed to delete habit:', response.statusText);
            }
        })
        .catch(error => {
            console.error('Error deleting habit:', error);
        });
    }

    return (
        <AuthenticatedLayout
            auth={props.auth}
            errors={props.errors}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Habits Transactions</h2>}
        >
            <Head title="Habits Transactions" />
            <div className={"max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8"}>
                <div className="my-2">
                        <Link href={route('habits.transactions.create')} className="inline-flex items-center mx-1 px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 false ">Add Item</Link>
                        <Link href={anyHabitActive ? route('habits.transactions.timer.end') : route('habits.transactions.timer.create')} className="inline-flex items-center mx-1 px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 false ">{anyHabitActive ? "Stop Timer" : "Start Timer"}</Link>
                </div>
                <table className="bg-gray-800 text-white w-full">
                    <thead>
                        <tr className="text-gray-500 uppercase font-medium">
                        <th className="px-4 py-3">ID</th>
                        <th className="px-4 py-3">Habit Name</th>
                        <th className="px-4 py-3">Start Time</th>
                        <th className="px-4 py-3">End Time</th>
                        <th className="px-4 py-3">Duration</th>
                        <th className="px-4 py-3">Delete</th>
                        </tr>
                    </thead>
                    <tbody>

                        { habits.data.map((item, index) => {
                            return (
                                <tr key={item.id}>
                                    <td className="px-4 py-3">{item.id}</td>
                                    <td className="px-4 py-3"><Link href={route('habits.transactions.edit', item.id)}>{item.name}</Link></td>
                                    <td className="px-4 py-3">{item.start_time}</td>
                                    <td className="px-4 py-3">{item.end_time}</td>
                                    <td className="px-4 py-3">{item.duration}</td>
                                    <td className="px-4 py-3"><a href="#" onClick={() => handleDelete(item.id)}>Delete</a></td>
                                </tr>
                            )
                        })}
                    </tbody>
                </table>

                <Pagination class="mt-6" total={lists.total} links={lists.links} />
            </div>
        </AuthenticatedLayout>
    )
}