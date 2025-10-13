import axios from 'axios';
import { useEffect, useState } from 'react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

function SimpleBarChart({color, habitId}) {
    const [chartData, setChartData] = useState([]);

    function getData() {
        axios.get(route('habits.show.get-habit-information', habitId))
        .then(function(response) {
            setChartData(response.data);
        });
    }

    useEffect(() => {
        getData();
    }, []);

    return (
        <div className="py-2">
            { chartData && (
            <>
                <h2 className="text-xl font-semibold text-white mb-5">Activity Chart</h2>
                {/* <div className="flex space-x-2"> */}
                {/*     {['30d', '90d', 'all'].map((period) => ( */}
                {/*         <button */}
                {/*             key={period} */}
                {/*             className={`px-3 py-1 rounded text-sm font-medium transition-colors ${ */}
                {/*                 true                                    ? 'bg-blue-600 text-white' */}
                {/*                     : 'bg-gray-600 text-gray-300 hover:bg-gray-500' */}
                {/*             }`} */}
                {/*         > */}
                {/*             {period} */}
                {/*         </button> */}
                {/*     ))} */}
                {/* </div> */}
                <div className="bg-white dark:bg-gray-800 rounded-lg p-6">
                    <ResponsiveContainer width="100%" height={300}>
                        <BarChart data={chartData ?? []} margin={{ top: 5, right: 0, left: -25, bottom: 5 }}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="date_column"  />
                            <YAxis />
                            <Tooltip labelFormatter={(label) => `Date: ${label}`} formatter={(value) => [`${value}`, 'Value']} />
                            <Bar dataKey="total_duration" fill={color || "#8884d8"} />
                        </BarChart>
                    </ResponsiveContainer>
                </div>
            </>
            )}
        </div>
    )
}

export default SimpleBarChart;
