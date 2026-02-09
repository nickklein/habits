import { PieChart, Pie, Cell, Tooltip, ResponsiveContainer, Legend } from 'recharts';

const COLORS = ['#5C6BC0', '#26A69A', '#EF5350', '#FFA726', '#AB47BC', '#42A5F5', '#66BB6A', '#EC407A', '#8D6E63', '#78909C'];

function TagBreakdownChart({ data, color }) {
    if (!data || data.length === 0) {
        return null;
    }

    const CustomTooltip = ({ active, payload }) => {
        if (active && payload && payload.length) {
            const entry = payload[0].payload;
            return (
                <div className="bg-gray-800 text-white px-3 py-2 rounded shadow text-sm">
                    <p className="font-medium">{entry.name}</p>
                    <p>{entry.formatted}</p>
                </div>
            );
        }
        return null;
    };

    return (
        <div className="py-2">
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-5">Tag Breakdown</h2>
            <div className="bg-white dark:bg-gray-800 rounded-lg p-6">
                <ResponsiveContainer width="100%" height={350}>
                    <PieChart>
                        <Pie
                            data={data}
                            cx="50%"
                            cy="50%"
                            outerRadius={120}
                            dataKey="value"
                            nameKey="name"
                            label={({ name, percent }) => `${name} (${(percent * 100).toFixed(0)}%)`}
                        >
                            {data.map((entry, index) => (
                                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                            ))}
                        </Pie>
                        <Tooltip content={<CustomTooltip />} />
                        <Legend />
                    </PieChart>
                </ResponsiveContainer>
            </div>
        </div>
    );
}

export default TagBreakdownChart;
