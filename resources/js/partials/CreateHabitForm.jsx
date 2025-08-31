import React from 'react'
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import SelectOptions from '@/Components/SelectOptions';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';
import { Transition } from '@headlessui/react';
import { useRef } from 'react';

function CreateHabitForm(props) {
    const nameInput = useRef();

    const { data, setData, errors, post, reset, processing, recentlySuccessful } = useForm({
        name: '',
        color_index: '0',
        streak_goal: 1,
        streak_time_type: 'daily',
        habit_type: 'time'
    });

    const habitTypeOptions = [
        { value: 'time', label: 'Time' },
        { value: 'ml', label: 'ML' },
        { value: 'unit', label: 'Unit' }
    ];

    const streakTimeTypeOptions = [
        { value: 'daily', label: 'Daily' },
        { value: 'weekly', label: 'Weekly' }
    ];

    const colorOptions = [
        { value: '0', label: 'Color 1' },
        { value: '1', label: 'Color 2' },
        { value: '2', label: 'Color 3' },
        { value: '3', label: 'Color 4' },
        { value: '4', label: 'Color 5' },
        { value: '5', label: 'Color 6' },
        { value: '6', label: 'Color 7' },
        { value: '7', label: 'Color 8' }
    ];

    function onSubmit(event) {
        event.preventDefault();
        post(route('habits.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: () => {
                if (errors.name) {
                    reset('name');
                    nameInput.current.focus();
                }
            },
        });
    }

    return (
        <>
            <section className="space-y-6">
                <header>
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">Create New Habit</h2>
                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Add a new habit to track your progress and build better routines.
                    </p>
                </header>
            </section>

            <form onSubmit={onSubmit} className="mt-6 space-y-6">
                <div>
                    <InputLabel htmlFor="name" value="Habit Name" />
                    <TextInput
                        id="name"
                        ref={nameInput}
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        type="text"
                        className="mt-1 block w-full"
                        placeholder="Enter habit name"
                        required
                        isFocused={true}
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="color_index" value="Color" />
                    <SelectOptions 
                        id="color_index"
                        options={colorOptions}
                        value={data.color_index}
                        onChange={(event) => setData('color_index', event.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.color_index} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="habit_type" value="Habit Type" />
                    <SelectOptions 
                        id="habit_type"
                        options={habitTypeOptions}
                        value={data.habit_type}
                        onChange={(event) => setData('habit_type', event.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.habit_type} className="mt-2" />
                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Time: Track duration, ML: Track volume, Unit: Track quantity
                    </p>
                </div>

                <div>
                    <InputLabel htmlFor="streak_goal" value="Streak Goal" />
                    <TextInput
                        id="streak_goal"
                        value={data.streak_goal}
                        onChange={(event) => setData('streak_goal', event.target.value)}
                        type="number"
                        min="1"
                        className="mt-1 block w-full"
                        required
                    />
                    <InputError message={errors.streak_goal} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="streak_time_type" value="Streak Time Type" />
                    <SelectOptions 
                        id="streak_time_type"
                        options={streakTimeTypeOptions}
                        value={data.streak_time_type}
                        onChange={(event) => setData('streak_time_type', event.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.streak_time_type} className="mt-2" />
                </div>

                <div className="flex items-center gap-4">
                    <PrimaryButton disabled={processing}>
                        {processing ? 'Creating...' : 'Create Habit'}
                    </PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-gray-600 dark:text-gray-400">Habit created successfully!</p>
                    </Transition>
                </div>
            </form>
        </>
    )
}

export default CreateHabitForm