import Pagination from '@/Components/Pagination';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import TwoHorizontalBarChart from '../partials/TwoHorizontalBarChart';
import SimpleBarChart from '../partials/SimpleBarChart';
import YearlyComparisonChart from '../partials/YearlyComparisonChart';
import { getTextColor, getBackgroundColor } from '@/Helpers/Colors'; // import helper functions
import StreakStats from '../partials/StreaksStats';
import TagBreakdownChart from '../partials/TagBreakdownChart';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { useState } from 'react';

export default function Index(props) {
    const {links} = usePage().props;
    const [chartData, setChartData] = useState(props.weeklyCharts || []);
    // @todo move this out of the dailySummaryHighlights method
    let color = getTextColor(props.color);
    let bgColor = getBackgroundColor(props.color);

    const handlePeriodChange = async (period) => {
        try {
            const response = await fetch(`/habit/show/${props.habit.habit.habit_id}/charts?period=${period}`);
            if (response.ok) {
                const newData = await response.json();
                setChartData(newData);
            }
        } catch (error) {
            console.error('Failed to fetch chart data:', error);
        }
    };

    return (
        <AuthenticatedLayout
            auth={props.auth}
            errors={props.errors}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{props.habit.habit.name}</h2>}
        >
            <Head title="Habits Insight" />
            <div className={"max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8"}>

                {props.streaks.goals && (
                    <div className="py-2">
                        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-5">Streaks</h2>
                        <StreakStats
                            color={color}
                            goals={props.streaks.goals}
                            goalsType={props.streaks.goalsType}
                            currentStreak={props.streaks.currentStreak}
                            bestStreak={props.streaks.longestStreak}
                            totalDaysDone={props.streaks.totalStreaks}
                        />
                    </div>
                )}

                <SimpleBarChart data={props.weeklyCharts} color={color} habitId={props.habit.habit.habit_id} />
                <YearlyComparisonChart habitId={props.habit.habit.habit_id} color={color} />
                <TagBreakdownChart data={props.tagBreakdown} color={color} />

                <div className="py-2">
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-5">Daily Highlights</h2>
                    <TwoHorizontalBarChart
                        title={props.habit.habit.name + ' Summary'}
                        color={color}
                        bgColor={bgColor}
                        description={props.dailySummaryHighlights.description}
                        barOne={{number: props.dailySummaryHighlights.barOne.number, unit: props.dailySummaryHighlights.barOne.unit, barText: props.dailySummaryHighlights.barOne.bar_text, width: props.dailySummaryHighlights.barOne.width}}
                        barTwo={{number: props.dailySummaryHighlights.barTwo.number, unit: props.dailySummaryHighlights.barTwo.unit, barText: props.dailySummaryHighlights.barTwo.bar_text, width: props.dailySummaryHighlights.barTwo.width}}
                    />
                </div>

                <div className="py-2">
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-5">Weekly Highlights</h2>
                    <TwoHorizontalBarChart
                        title={props.habit.habit.name + ' Averages'}
                        color={color}
                        bgColor={bgColor}
                        description={props.weeklyAveragesHighlights.description}
                        barOne={{number: props.weeklyAveragesHighlights.barOne.number, unit: props.weeklyAveragesHighlights.barOne.unit, barText: props.weeklyAveragesHighlights.barOne.bar_text, width: props.weeklyAveragesHighlights.barOne.width}}
                        barTwo={{number: props.weeklyAveragesHighlights.barTwo.number, unit: props.weeklyAveragesHighlights.barTwo.unit, barText: props.weeklyAveragesHighlights.barTwo.bar_text, width: props.weeklyAveragesHighlights.barTwo.width}}
                    />

                    <TwoHorizontalBarChart
                        title={props.habit.habit.name + ' Summary'}
                        color={color}
                        bgColor={bgColor}
                        description={props.weeklySummaryHighlights.description}
                        barOne={{number: props.weeklySummaryHighlights.barOne.number, unit: props.weeklySummaryHighlights.barOne.unit, barText: props.weeklySummaryHighlights.barOne.bar_text, width: props.weeklySummaryHighlights.barOne.width}}
                        barTwo={{number: props.weeklySummaryHighlights.barTwo.number, unit: props.weeklySummaryHighlights.barTwo.unit, barText: props.weeklySummaryHighlights.barTwo.bar_text, width: props.weeklyAveragesHighlights.barTwo.width}}
                    />

                </div>

                <div className="py-2">
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-5">Monthly Highlights</h2>
                    <TwoHorizontalBarChart
                        title={props.habit.habit.name  + ' Averages'}
                        color={color}
                        bgColor={bgColor}
                        description={props.monthlyAveragesHighlights.description}
                        barOne={{number: props.monthlyAveragesHighlights.barOne.number, unit: props.monthlyAveragesHighlights.barOne.unit, barText: props.monthlyAveragesHighlights.barOne.bar_text, width: props.monthlyAveragesHighlights.barOne.width}}
                        barTwo={{number: props.monthlyAveragesHighlights.barTwo.number, unit: props.monthlyAveragesHighlights.barTwo.unit, barText: props.monthlyAveragesHighlights.barTwo.bar_text, width: props.monthlyAveragesHighlights.barTwo.width}}
                    />

                    <TwoHorizontalBarChart
                        title={props.habit.habit.name  + ' Summary'}
                        color={color}
                        bgColor={bgColor}
                        description={props.monthlySummaryHighlights.description}
                        barOne={{number: props.monthlySummaryHighlights.barOne.number, unit: props.monthlySummaryHighlights.barOne.unit, barText: props.monthlySummaryHighlights.barOne.bar_text, width: props.monthlySummaryHighlights.barOne.width}}
                        barTwo={{number: props.monthlySummaryHighlights.barTwo.number, unit: props.monthlySummaryHighlights.barTwo.unit, barText: props.monthlySummaryHighlights.barTwo.bar_text, width: props.monthlySummaryHighlights.barTwo.width}}
                    />

                </div>

                <div className="py-2">
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-5">Yearly Highlights</h2>
                    <TwoHorizontalBarChart
                        title={props.habit.habit.name  + ' Summary'}
                        color={color}
                        bgColor={bgColor}
                        description={props.yearlySummaryHighlights.description}
                        barOne={{number: props.yearlySummaryHighlights.barOne.number, unit: props.yearlySummaryHighlights.barOne.unit, barText: props.yearlySummaryHighlights.barOne.bar_text, width: props.yearlySummaryHighlights.barOne.width}}
                        barTwo={{number: props.yearlySummaryHighlights.barTwo.number, unit: props.yearlySummaryHighlights.barTwo.unit, barText: props.yearlySummaryHighlights.barTwo.bar_text, width: props.yearlySummaryHighlights.barTwo.width}}
                    />
                </div>

                <div className="py-2">
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-5">Total To Date</h2>
                    <TwoHorizontalBarChart
                        title={props.habit.habit.name  + ' Summary'}
                        color={color}
                        bgColor={bgColor}
                        description={props.totalSummaryHighlights.description}
                        barOne={{number: props.totalSummaryHighlights.barOne.number, unit: props.totalSummaryHighlights.barOne.unit, barText: props.totalSummaryHighlights.barOne.bar_text, width: props.yearlySummaryHighlights.barOne.width}}
                    />
                </div>
            </div>
        </AuthenticatedLayout>
    )
}
