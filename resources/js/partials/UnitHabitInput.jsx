import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { FaPlus, FaMinus, FaCheck } from 'react-icons/fa';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

function UnitHabitInput({ habitUser }) {
    const { auth } = usePage().props;
    const [count, setCount] = useState(1);
    const [loading, setLoading] = useState(false);
    const [success, setSuccess] = useState(false);

    const handleIncrement = () => {
        setCount(prev => prev + 1);
    };

    const handleDecrement = () => {
        setCount(prev => Math.max(1, prev - 1));
    };

    const handlePresetClick = (value) => {
        setCount(prev => prev + value);
    };

    const handleSubmit = () => {
        if (count < 1) return;

        setLoading(true);

        axios.post(route('api.habits.save-value', { habitId: habitUser.habit_id }), { value: count })
            .then(response => {
                setSuccess(true);
                setTimeout(() => {
                    router.visit(route('habits.index'));
                }, 1000);
            })
            .catch(error => {
                console.error('Error saving count:', error);
            })
            .finally(() => {
                setLoading(false);
            });
    };

    return (
        <div className="flex flex-col items-center justify-center py-12">
            <div className="text-center mb-8">
                <div className="text-7xl font-bold text-gray-900 dark:text-white mb-8">
                    {count}
                </div>

                <div className="flex items-center justify-center gap-4 mb-8">
                    <button
                        onClick={handleDecrement}
                        disabled={loading || count <= 1}
                        className="w-16 h-16 flex-shrink-0 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white text-2xl flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <FaMinus />
                    </button>

                    <input
                        type="number"
                        value={count}
                        onChange={(e) => {
                            const value = parseInt(e.target.value);
                            if (!isNaN(value) && value >= 1) {
                                setCount(value);
                            }
                        }}
                        disabled={loading}
                        className="w-24 text-center text-2xl font-bold bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg px-2 py-2 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50"
                    />

                    <button
                        onClick={handleIncrement}
                        disabled={loading}
                        className="w-16 h-16 flex-shrink-0 rounded-full bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white text-2xl flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <FaPlus />
                    </button>
                </div>

                <div className="flex gap-2 justify-center mb-8">
                    <button
                        onClick={() => handlePresetClick(5)}
                        disabled={loading}
                        className="px-6 py-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors disabled:opacity-50"
                    >
                        +5
                    </button>
                    <button
                        onClick={() => handlePresetClick(10)}
                        disabled={loading}
                        className="px-6 py-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors disabled:opacity-50"
                    >
                        +10
                    </button>
                    <button
                        onClick={() => handlePresetClick(25)}
                        disabled={loading}
                        className="px-6 py-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors disabled:opacity-50"
                    >
                        +25
                    </button>
                </div>

                <div className="flex gap-4 justify-center">
                    <PrimaryButton
                        onClick={handleSubmit}
                        processing={loading}
                        disabled={loading || count < 1}
                        className="min-w-32"
                    >
                        {success ? (
                            <>
                                <FaCheck className="inline mr-2" />
                                Saved!
                            </>
                        ) : (
                            'Add'
                        )}
                    </PrimaryButton>

                    <SecondaryButton
                        onClick={() => router.visit(route('habits.index'))}
                        disabled={loading}
                    >
                        Cancel
                    </SecondaryButton>
                </div>
            </div>
        </div>
    );
}

export default UnitHabitInput;
