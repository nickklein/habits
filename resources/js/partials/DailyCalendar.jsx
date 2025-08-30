import React, { useState, useEffect } from 'react';
import { FaChevronLeft, FaChevronRight } from 'react-icons/fa';

export default function DailyCalendar({ onDateSelect, selectedDate = null }) {
    const [currentDate, setCurrentDate] = useState(selectedDate || new Date());
    const [days, setDays] = useState([]);
    const [weekOffset, setWeekOffset] = useState(0);
    const [touchStart, setTouchStart] = useState(null);
    const [touchEnd, setTouchEnd] = useState(null);
    console.log(currentDate);

    useEffect(() => {
        generateDays();
    }, [weekOffset]);

    const generateDays = () => {
        const today = new Date();
        const daysArray = [];
        
        for (let i = 6; i >= 0; i--) {
            const date = new Date(today);
            date.setDate(today.getDate() - i - (weekOffset * 7));
            daysArray.push(date);
        }
        
        setDays(daysArray);
    };

    const handleDateClick = (date) => {
        setCurrentDate(date);
        if (onDateSelect) {
            // Format date as YYYY-MM-DD for the API
            const formattedDate = date.toISOString().split('T')[0];
            onDateSelect(formattedDate);
        }
    };

    const getDayName = (date) => {
        return date.toLocaleDateString('en-US', { 
            weekday: 'short' 
        });
    };

    const isToday = (date) => {
        const today = new Date();
        return date.toDateString() === today.toDateString();
    };

    const isSelected = (date) => {
        return date.toDateString() === currentDate.toDateString();
    };

    const goToPreviousWeek = () => {
        setWeekOffset(weekOffset + 1);
    };

    const goToNextWeek = () => {
        if (weekOffset > 0) {
            setWeekOffset(weekOffset - 1);
        }
    };

    const canGoNext = weekOffset > 0;

    const minSwipeDistance = 50;

    const onTouchStart = (e) => {
        setTouchEnd(null);
        setTouchStart(e.targetTouches[0].clientX);
    };

    const onTouchMove = (e) => {
        setTouchEnd(e.targetTouches[0].clientX);
    };

    const onTouchEnd = () => {
        if (!touchStart || !touchEnd) return;
        
        const distance = touchStart - touchEnd;
        const isLeftSwipe = distance > minSwipeDistance;
        const isRightSwipe = distance < -minSwipeDistance;

        if (isLeftSwipe) {
            goToPreviousWeek();
        }
        if (isRightSwipe && canGoNext) {
            goToNextWeek();
        }
    };

    return (
        <div className="py-4 w-full">
            <div className="flex items-center gap-2 w-full">
                <button
                    onClick={goToPreviousWeek}
                    className="flex-shrink-0 p-2 text-gray-300 hover:bg-gray-600 rounded-lg transition-colors duration-200 hidden sm:block"
                >
                    <FaChevronLeft size={16} />
                </button>
                
                <div 
                    className="grid grid-cols-7 gap-1 sm:gap-2 flex-1"
                    onTouchStart={onTouchStart}
                    onTouchMove={onTouchMove}
                    onTouchEnd={onTouchEnd}
                >
                {days.map((date, index) => (
                    <div
                        key={index}
                        onClick={() => handleDateClick(date)}
                        className={`
                            flex flex-col items-center p-2 sm:p-3 rounded-lg cursor-pointer transition-all duration-200
                            ${isSelected(date) 
                                ? 'bg-blue-600 text-white shadow-lg' 
                                : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
                            }
                            ${isToday(date) && !isSelected(date) 
                                ? 'ring-2 ring-blue-400' 
                                : ''
                            }
                            w-full
                        `}
                    >
                        <span className="text-xs font-medium mb-1 text-center">
                            {getDayName(date)}
                        </span>
                        <span className="text-sm sm:text-base font-bold text-center">
                            {date.getDate()}
                        </span>
                        <span className="text-xs opacity-75 text-center">
                            {date.toLocaleDateString('en-US', { month: 'short' })}
                        </span>
                    </div>
                ))}
                </div>
                
                <button
                    onClick={goToNextWeek}
                    disabled={!canGoNext}
                    className={`flex-shrink-0 p-2 rounded-lg transition-colors duration-200 hidden sm:block ${
                        canGoNext 
                            ? 'text-gray-300 hover:bg-gray-600' 
                            : 'bg-gray-800 text-gray-500 cursor-not-allowed'
                    }`}
                >
                    <FaChevronRight size={16} />
                </button>
            </div>
        </div>
    );
}
