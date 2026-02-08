import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { FaCheck } from 'react-icons/fa';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import TagsInput from '@/Components/TagsInput';

function MLHabitInput({ habitUser, popularTags = [] }) {
    const { auth } = usePage().props;
    const [volume, setVolume] = useState(250);
    const [loading, setLoading] = useState(false);
    const [success, setSuccess] = useState(false);
    const [tags, setTags] = useState([]);
    const [newTag, setNewTag] = useState('');

    const formatVolume = (ml) => {
        if (ml >= 1000) {
            return `${(ml / 1000).toFixed(2)} L`;
        }
        return `${ml} ml`;
    };

    const handlePresetClick = (value) => {
        setVolume(value);
    };

    const handleAddTag = (event) => {
        event.preventDefault();
        if (newTag) {
            setTags([...tags, newTag]);
            setNewTag('');
        }
    };

    const handleRemoveTag = (index) => {
        setTags(tags.filter((_, i) => i !== index));
    };

    const handleSubmit = () => {
        if (volume < 1) return;

        setLoading(true);

        axios.post(route('api.habits.save-value', { habitId: habitUser.habit_id }), { value: volume })
            .then(response => {
                const habitTimeId = response.data.habitTimeId;
                const tagPromises = tags.map(tag =>
                    axios.post(route('habits.transactions.edit.add-tag', { habitTimesId: habitTimeId }), { tagName: tag })
                );
                return Promise.all(tagPromises);
            })
            .then(() => {
                setSuccess(true);
                setTimeout(() => {
                    router.visit(route('habits.index'));
                }, 1000);
            })
            .catch(error => {
                console.error('Error saving volume:', error);
            })
            .finally(() => {
                setLoading(false);
            });
    };

    return (
        <div className="flex flex-col items-center justify-center py-12">
            <div className="text-center mb-8">
                <div className="text-6xl font-bold text-gray-900 dark:text-white mb-2">
                    {formatVolume(volume)}
                </div>

                <div className="text-sm text-gray-600 dark:text-gray-400 mb-8">
                    {volume >= 1000 ? `(${volume} ml)` : ''}
                </div>

                <div className="mb-8 max-w-xs mx-auto">
                    <TextInput
                        type="number"
                        value={volume}
                        handleChange={(e) => {
                            const value = parseInt(e.target.value);
                            if (!isNaN(value) && value >= 1) {
                                setVolume(value);
                            }
                        }}
                        className="text-center text-2xl font-bold"
                        disabled={loading}
                        placeholder="Enter volume (ml)"
                    />
                </div>

                <div className="mb-8">
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        Quick Add:
                    </div>
                    <div className="grid grid-cols-3 gap-3 max-w-md mx-auto">
                        <button
                            onClick={() => handlePresetClick(250)}
                            disabled={loading}
                            className={`px-6 py-4 rounded-lg transition-all ${
                                volume === 250
                                    ? 'bg-blue-500 text-white dark:bg-blue-600'
                                    : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50'
                            } disabled:opacity-50`}
                        >
                            <div className="font-bold text-lg">250ml</div>
                            <div className="text-xs opacity-75">Glass</div>
                        </button>
                        <button
                            onClick={() => handlePresetClick(500)}
                            disabled={loading}
                            className={`px-6 py-4 rounded-lg transition-all ${
                                volume === 500
                                    ? 'bg-blue-500 text-white dark:bg-blue-600'
                                    : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50'
                            } disabled:opacity-50`}
                        >
                            <div className="font-bold text-lg">500ml</div>
                            <div className="text-xs opacity-75">Bottle</div>
                        </button>
                        <button
                            onClick={() => handlePresetClick(1000)}
                            disabled={loading}
                            className={`px-6 py-4 rounded-lg transition-all ${
                                volume === 1000
                                    ? 'bg-blue-500 text-white dark:bg-blue-600'
                                    : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50'
                            } disabled:opacity-50`}
                        >
                            <div className="font-bold text-lg">1L</div>
                            <div className="text-xs opacity-75">Large Bottle</div>
                        </button>
                        <button
                            onClick={() => handlePresetClick(350)}
                            disabled={loading}
                            className={`px-6 py-4 rounded-lg transition-all ${
                                volume === 350
                                    ? 'bg-blue-500 text-white dark:bg-blue-600'
                                    : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50'
                            } disabled:opacity-50`}
                        >
                            <div className="font-bold text-lg">350ml</div>
                            <div className="text-xs opacity-75">Can</div>
                        </button>
                        <button
                            onClick={() => handlePresetClick(750)}
                            disabled={loading}
                            className={`px-6 py-4 rounded-lg transition-all ${
                                volume === 750
                                    ? 'bg-blue-500 text-white dark:bg-blue-600'
                                    : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50'
                            } disabled:opacity-50`}
                        >
                            <div className="font-bold text-lg">750ml</div>
                            <div className="text-xs opacity-75">Sport Bottle</div>
                        </button>
                        <button
                            onClick={() => handlePresetClick(2000)}
                            disabled={loading}
                            className={`px-6 py-4 rounded-lg transition-all ${
                                volume === 2000
                                    ? 'bg-blue-500 text-white dark:bg-blue-600'
                                    : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50'
                            } disabled:opacity-50`}
                        >
                            <div className="font-bold text-lg">2L</div>
                            <div className="text-xs opacity-75">Jug</div>
                        </button>
                    </div>
                </div>

                <div className="mb-8 max-w-md mx-auto w-full">
                    <div className="text-sm text-gray-600 dark:text-gray-400 mb-2">Tags</div>
                    <TagsInput
                        selected={tags}
                        handleAddTag={handleAddTag}
                        handleRemoveTag={handleRemoveTag}
                        setInpuTag={(e) => setNewTag(e.target.value)}
                        value={newTag}
                        popularTags={popularTags}
                        onPopularTagClick={(tag) => setTags([...tags, tag])}
                    />
                </div>

                <div className="flex gap-4 justify-center">
                    <PrimaryButton
                        onClick={handleSubmit}
                        processing={loading}
                        disabled={loading || volume < 1}
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
                        Back to Habits
                    </SecondaryButton>

                    <SecondaryButton
                        onClick={() => router.visit(route('habits.show', { habitId: habitUser.habit_id }))}
                        disabled={loading}
                    >
                        Go to Insights
                    </SecondaryButton>
                </div>
            </div>
        </div>
    );
}

export default MLHabitInput;
