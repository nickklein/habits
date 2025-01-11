import React from 'react';
import { FaFire, FaTrophy, FaCalendarDay, FaCheck } from 'react-icons/fa';

function StreakStats(props) {
    return (
        <div className="bg-gray-800 shadow-lg rounded-lg p-6 mt-4 relative">
            <h3 className={"text-xl font-semibold mb-3 " + props.color}>Streak Stats</h3>
            <p className="text-white mb-5 text-xl font-semibold">Keep the momentum! Goal: {props.goals}</p>
            <hr className="border-gray-600 mb-5"/>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="flex items-center">
                    <FaFire className={"text-3xl mr-3 " + props.color} />
                    <div>
                        <span className="text-xl text-gray-400 font-semibold block mb-2">Current Streak</span>
                        <span className="text-4xl text-white font-semibold">{props.currentStreak}</span>
                    </div>
                </div>

                <div className="flex items-center">
                    <FaTrophy className={"text-3xl mr-3 " + props.color} />
                    <div>
                        <span className="text-xl text-gray-400 font-semibold block mb-2">Best Streak</span>
                        <span className="text-4xl text-white font-semibold">{props.bestStreak}</span>
                    </div>
                </div>

                <div className="flex items-center">
                    <FaCheck className={"text-3xl mr-3 " + props.color} />
                    <div>
                        <span className="text-xl text-gray-400 font-semibold block mb-2">Total {props.goalsType} Done</span>
                        <span className="text-4xl text-white font-semibold">{props.totalDaysDone}</span>
                    </div>
                </div>
            </div>
        </div>
    )
}

export default StreakStats;
