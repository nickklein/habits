import React, { useState, useEffect, useRef } from 'react';
import { router, usePage } from '@inertiajs/react';
import { FaPlay, FaPause } from 'react-icons/fa';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

function TimeHabitTimer({ habitUser }) {
    const { auth } = usePage().props;
    const [isRunning, setIsRunning] = useState(habitUser.is_active);
    const [elapsedTime, setElapsedTime] = useState(0);
    const [loading, setLoading] = useState(false);
    const intervalRef = useRef(null);

    useEffect(() => {
        if (isRunning) {
            fetchCurrentElapsedTime();
            intervalRef.current = setInterval(() => {
                setElapsedTime(prev => prev + 1);
            }, 1000);
        } else {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        }

        return () => {
            if (intervalRef.current) {
                clearInterval(intervalRef.current);
            }
        };
    }, [isRunning]);

    const fetchCurrentElapsedTime = async () => {
        try {
            const response = await fetch(route('api.habits.summary', { habitUserId: habitUser.id, page: 'home' }));
            if (response.ok) {
                const data = await response.json();
                if (data.is_active && data.active_elapsed_seconds != null) {
                    setElapsedTime(data.active_elapsed_seconds);
                }
            }
        } catch (error) {
            console.error('Error fetching elapsed time:', error);
        }
    };

    const formatTime = (seconds) => {
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;

        return `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    };

    const handleToggleTimer = () => {
        setLoading(true);
        const status = isRunning ? 'off' : 'on';

        axios.post(route('api.habits.timer.toggle', { habitId: habitUser.habit_id, status: status }))
            .then(response => {
                setIsRunning(!isRunning);
                if (!isRunning) {
                    setElapsedTime(0);
                } else {
                    router.visit(route('habits.index'));
                }
            })
            .catch(error => {
                console.error('Error toggling timer:', error);
            })
            .finally(() => {
                setLoading(false);
            });
    };

    return (
        <div className="flex flex-col items-center justify-center py-12">
            <div className="text-center mb-8">
                <div className={`text-7xl font-mono font-bold mb-8 ${isRunning ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white'}`}>
                    {formatTime(elapsedTime)}
                </div>

                <button
                    onClick={handleToggleTimer}
                    disabled={loading}
                    className={`
                        w-24 h-24 rounded-full flex items-center justify-center text-white text-3xl
                        transition-all duration-200 transform hover:scale-105 active:scale-95
                        ${isRunning
                            ? 'bg-orange-500 hover:bg-orange-600 dark:bg-orange-600 dark:hover:bg-orange-700'
                            : 'bg-green-500 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700'
                        }
                        ${isRunning ? 'animate-pulse' : ''}
                        ${loading ? 'opacity-50 cursor-not-allowed' : ''}
                    `}
                >
                    {isRunning ? <FaPause /> : <FaPlay className="ml-1" />}
                </button>

                <div className="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    {isRunning ? 'Tap to pause and save' : 'Tap to start tracking'}
                </div>
            </div>

            <div className="mt-8">
                <SecondaryButton
                    onClick={() => router.visit(route('habits.index'))}
                    disabled={loading}
                >
                    Back to Habits
                </SecondaryButton>
            </div>
        </div>
    );
}

export default TimeHabitTimer;
