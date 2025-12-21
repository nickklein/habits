import React from 'react';
import Card from './Card';

export default function SkeletonTile() {
    return (
        <Card className="flex justify-between animate-pulse">
            <div className="flex-1">
                <div className="h-6 bg-gray-300 dark:bg-gray-600 rounded mb-3 w-32"></div>
                <div className="flex items-baseline gap-2">
                    <div className="h-10 bg-gray-300 dark:bg-gray-600 rounded w-16"></div>
                    <div className="h-4 bg-gray-300 dark:bg-gray-600 rounded w-12"></div>
                </div>
            </div>
        </Card>
    );
}