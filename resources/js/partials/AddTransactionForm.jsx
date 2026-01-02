import React from 'react';
import TimeHabitTimer from './TimeHabitTimer';
import UnitHabitInput from './UnitHabitInput';
import MLHabitInput from './MLHabitInput';

function AddTransactionForm({ habitUser }) {
    const renderHabitInput = () => {
        switch (habitUser.habit_type) {
            case 'time':
                return <TimeHabitTimer habitUser={habitUser} />;
            case 'unit':
                return <UnitHabitInput habitUser={habitUser} />;
            case 'ml':
                return <MLHabitInput habitUser={habitUser} />;
            default:
                return (
                    <div className="p-8 text-center text-gray-600 dark:text-gray-400">
                        Unknown habit type: {habitUser.habit_type}
                    </div>
                );
        }
    };

    return (
        <div className="p-6 sm:p-8">
            {renderHabitInput()}
        </div>
    );
}

export default AddTransactionForm;
