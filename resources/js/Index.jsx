import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import Card from './partials/Card';
import { getTextColor } from '@/Helpers/Colors'; // import helper functions
import { useState } from 'react';
import { FaChevronDown, FaChevronRight } from 'react-icons/fa';
import DailyCalendar from './partials/DailyCalendar';

export default function Index(props) {
    const {links} = usePage().props;
    const [openedChildIndex, setOpenedChildIndex] = useState(null);

    return (
        <AuthenticatedLayout
            auth={props.auth}
            errors={props.errors}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Habits</h2>}
        >
            <Head title="Habits" />
            <div className={"max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8"}>
                <DailyCalendar />
                {props.habits.map((habit, index) => {
                    let color = getTextColor(habit.color);
                    return (
                        <>
                            <Card className="flex justify-between">
                                <div>
                                    <h3 className="text-xl font-semibold" style={{ color: color }}><Link href={route('habits.show', habit.id)}>{habit.name}</Link></h3>
                                    <div className="text-4xl font-bold text-white">
                                        {habit.current.total} <span className="text-gray-400 text-sm">{habit.current.unit}</span> { habit.goal.total && ( <>/ {habit.goal.total} <span className="text-gray-400 text-sm">{habit.goal.unit} {habit.goal.type}</span></> )}
                                    </div>
                                </div>
                                { habit.children && (
                                    <a href="#" onClick={(e) => { e.preventDefault(); setOpenedChildIndex(openedChildIndex === index ? null : index)}} className="text-white mr-4 inline-flex items-center">
                                        {openedChildIndex === index ? <FaChevronDown size={22} /> : <FaChevronRight size={22} />}
                                    </a>
                                )}
                            </Card>
                            {openedChildIndex === index && habit.children && habit.children.map((child, index) => {
                                
                                return (
                                    <Card className={"ml-5"}>
                                        <h3 className="text-xl font-semibold" style={{ color: color }}><Link href={route('habits.show', child.id)}>{child.name}</Link></h3>
                                        <div className="text-4xl font-bold text-white">
                                            {child.current.total} <span className="text-gray-400 text-sm">{child.current.unit}</span> { child.goal.total && ( <>/ {child.goal.total} <span className="text-gray-400 text-sm">{child.goal.unit} {child.goal.type}</span></> )}
                                        </div>
                                    </Card>
                                )
                            })}
                        </>
                    )
                })}
            </div>
        </AuthenticatedLayout>
    )
}
