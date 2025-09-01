import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { FaChevronDown, FaChevronRight, FaExclamationTriangle, FaPlay } from 'react-icons/fa';
import Card from './Card';
import SkeletonTile from './SkeletonTile';
import { getTextColor } from '@/Helpers/Colors';

const INSIGHT_TYPE = 'insights';

export default function HabitTile({ habitUserId, selectedDate, type }) {
    const [habitData, setHabitData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [childrenOpen, setChildrenOpen] = useState(false);

    useEffect(() => {
        fetchHabitData();
    }, [habitUserId, selectedDate]);

    const fetchHabitData = async () => {
        try {
            setLoading(true);
            setError(null);
            
            const url = fetchRoute();
            
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error('Failed to load habit data');
            }
            
            const data = await response.json();
            setHabitData(data);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const toggleChildren = (e) => {
        e.preventDefault();
        setChildrenOpen(!childrenOpen);
    };

    // TODO: Code smell. All this logic needs to go up a level
    const fetchRoute = () => {

        if (type === INSIGHT_TYPE) {
            return route('api.habits.insights.summary', habitUserId)
        }
        const baseUrl = route('api.habits.summary', habitUserId);
        return selectedDate 
            ? `${baseUrl}?date=${selectedDate}`
            : baseUrl;
    };

    if (loading) {
        return <SkeletonTile />;
    }

    if (error) {
        return (
            <Card className="flex items-center gap-3 bg-red-900/20 border-red-500/20">
                <FaExclamationTriangle className="text-red-400 text-lg" />
                <div>
                    <p className="text-red-400 font-medium">Failed to load habit</p>
                    <button 
                        onClick={fetchHabitData}
                        className="text-sm text-red-300 hover:text-red-200 underline"
                    >
                        Try again
                    </button>
                </div>
            </Card>
        );
    }

    if (!habitData) {
        return null;
    }

    const textColor = getTextColor(habitData.color_index);

    return (
        <>
            { (type === INSIGHT_TYPE || !habitData.goal_met) && (
                <Card className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="text-2xl">
                            {habitData.is_active ? (
                                <FaPlay className="text-green-400 animate-pulse" />
                            ) : (
                                <span style={{ color: textColor }}>{habitData.icon}</span>
                            )}
                        </div>
                        <div>
                            <h3 className="text-xl font-semibold" style={{ color: textColor }}>
                                <Link href={route('habits.show', habitData.id)}>
                                    {habitData.name}
                                    {habitData.is_active && (
                                        <span className="ml-2 text-green-400 text-sm font-medium">In Progress</span>
                                    )}
                                </Link>
                            </h3>
                            <div className="text-2xl font-bold text-white">
                                {habitData.current.total} <span className="text-gray-400 text-sm mr-3">{habitData.current.unit}</span> 
                                {habitData.goal.total && (
                                    <>
                                        / {habitData.goal.total} <span className="text-gray-400 text-sm">{habitData.goal.unit} {habitData.goal.type}</span>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                    {habitData.children && habitData.children.length > 0 && (
                        <button 
                            onClick={toggleChildren}
                            className="text-white mr-4 inline-flex items-center hover:text-gray-300 transition-colors"
                        >
                            {childrenOpen ? <FaChevronDown size={22} /> : <FaChevronRight size={22} />}
                        </button>
                    )}
                </Card>
            )}
            
            {childrenOpen && habitData.children && habitData.children.map((child, index) => (
                (type === INSIGHT_TYPE || !child.goal_met) && (
                    <Card key={index} className="ml-5">
                        <div className="flex items-center gap-3">
                            <div className="text-2xl">
                                {child.is_active ? (
                                    <FaPlay className="text-green-400 animate-pulse" />
                                ) : (
                                    <span style={{ color: textColor }}>{child.icon}</span>
                                )}
                            </div>
                            <div>
                                <h3 className="text-xl font-semibold" style={{ color: textColor }}>
                                    <Link href={route('habits.show', child.id)}>
                                        {child.name}
                                        {child.is_active && (
                                            <span className="ml-2 text-green-400 text-sm font-medium">In Progress</span>
                                        )}
                                    </Link>
                                </h3>
                                <div className="text-2xl font-bold text-white">
                                    {child.current.total} <span className="text-gray-400 text-sm mr-3">{child.current.unit}</span>
                                    {child.goal.total && (
                                        <>
                                            / {child.goal.total} <span className="text-gray-400 text-sm">{child.goal.unit} {child.goal.type}</span>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    </Card>
                )
            ))}
        </>
    );
}
