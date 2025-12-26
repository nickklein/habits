import axios from 'axios';
import { useEffect, useState } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

function YearlyComparisonChart({ habitId, color = '#10B981' }) {
    const [chartData, setChartData] = useState([]);
    const [years, setYears] = useState({ previous: null, current: null });
    const [loading, setLoading] = useState(true);

    function getData() {
        axios.get(route('habits.yearly-comparison.habit', habitId))
        .then(function(response) {
            setYears({
                previous: response.data.years.previous,
                current: response.data.years.current
            });
            setChartData(response.data.data);
            setLoading(false);
        })
        .catch(function(error) {
            console.error('Error fetching chart data:', error);
            setLoading(false);
        });
    }

    useEffect(() => {
        getData();
    }, [habitId]);

    const CustomTooltip = ({ active, payload, label }) => {
        if (active && payload && payload.length) {
            return (
                <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3 shadow-lg">
                    <p className="text-sm font-medium text-gray-900 dark:text-white mb-2">Day {label}</p>
                    {payload.map((entry, index) => (
                        <p key={index} className="text-sm" style={{ color: entry.color }}>
                            {entry.name}: {entry.value.toFixed(2)} hours
                        </p>
                    ))}
                </div>
            );
        }
        return null;
    };

    if (loading) {
        return (
            <div className="py-2">
                <div className="bg-white dark:bg-gray-800 rounded-lg p-6">
                    <div className="animate-pulse">
                        <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4 mb-4"></div>
                        <div className="h-64 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="py-2">
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-5">
                Path Chart
            </h2>
            <div className="bg-white dark:bg-gray-800 rounded-lg p-6">
                <ResponsiveContainer width="100%" height={400}>
                    <LineChart
                        data={chartData}
                        margin={{ top: 5, right: 30, left: 0, bottom: 5 }}
                    >
                        <CartesianGrid strokeDasharray="3 3" stroke="#374151" />
                        <XAxis
                            dataKey="day_of_year"
                            stroke="#9CA3AF"
                            label={{ value: 'Day of Year', position: 'insideBottom', offset: -5, fill: '#9CA3AF' }}
                        />
                        <YAxis
                            stroke="#9CA3AF"
                            label={{ value: 'Cumulative Hours', angle: -90, position: 'insideLeft', fill: '#9CA3AF' }}
                        />
                        <Tooltip content={<CustomTooltip />} />
                        <Legend
                            wrapperStyle={{ paddingTop: '10px' }}
                            iconType="line"
                        />
                        <Line
                            type="monotone"
                            dataKey={`total_${years.previous}`}
                            stroke="#4B5563"
                            strokeWidth={2}
                            dot={false}
                            name={years.previous}
                            connectNulls
                        />
                        <Line
                            type="monotone"
                            dataKey={`total_${years.current}`}
                            stroke={color}
                            strokeWidth={2}
                            dot={false}
                            name={years.current}
                            connectNulls
                        />
                    </LineChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}

export default YearlyComparisonChart;
