import React from 'react'
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import SelectOptions from '@/Components/SelectOptions';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Transition } from '@headlessui/react';
import { useEffect, useRef, useState } from 'react';


function EditHabitTimeForm(props) {
    const startTimeInput = useRef();

    const { data, setData, errors, put, reset, processing, recentlySuccessful } = useForm({
        habit_id: props.item.habit_id,
        habit_type: props.item.habit_type,
        start_date: props.item.start_date,
        start_time: props.item.start_time,
        end_date: props.item.end_date,
        end_time: props.item.end_time,
        value: props.item.duration,
    });


    function onSubmit(event) {
        event.preventDefault();

        put(route('habits.transactions.update', props.item.id), {
            preserveScroll: true,
            onSuccess: () => setData('start_time', data.start_time),
            onError: (err) => {
                console.log(err);
                if (errors.start_time) {
                    reset('start_time');
                }
            },
        });
    }
    useEffect(() => {
        const selectedHabit = props.habits.find(habit => habit.value == data.habit_id);
        if (selectedHabit) {
            setData('habit_type', selectedHabit.habit_type);
        }
    }, [data.habit_id]);

    return (
        <>
            <section className={`space-y-6`}>
                <header>
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">Edit Habit Transaction</h2>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Sometimes you gotta fix habit transaction and this is the place to do it.
                    </p>
                </header>
            </section>

            <form onSubmit={onSubmit} className="mt-6 space-y-6">

                <div>
                    <InputLabel for="habit" value="Habit" />

                    <SelectOptions 
                        options={props.habits}
                        value={data.habit_id}
                        onChange={(event) => setData('habit_id', event.target.value)}
                    />

                    <InputError message={errors.start_time} className="mt-2" />
                </div>

                <>
                    <div>
                        <InputLabel for="start_time" value="Start Time" />

                        <TextInput
                            id="start_date"
                            ref={startTimeInput}
                            value={data.start_date}
                            handleChange={(event) => setData('start_date', event.target.value)}
                            type="date"
                            className="mt-1 block w-full"
                        />

                        <TextInput
                            id="start_time"
                            ref={startTimeInput}
                            value={data.start_time}
                            handleChange={(event) => setData('start_time', event.target.value)}
                            type="time"
                            className="mt-1 block w-full"
                        />


                        <InputError message={errors.start_time} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="end_time" value="End Time" />

                        <TextInput
                            id="end_date"
                            ref={startTimeInput}
                            value={data.end_date}
                            handleChange={(event) => setData('end_date', event.target.value)}
                            type="date"
                            className="mt-1 block w-full"
                        />

                        <TextInput
                            id="end_time"
                            ref={startTimeInput}
                            value={data.end_time}
                            handleChange={(event) => setData('end_time', event.target.value)}
                            type="time"
                            className="mt-1 block w-full"
                        />

                        <InputError message={errors.start_time} className="mt-2" />
                    </div>
                </>

                { data.habit_type !== 'time' && (
                    <div>
                        <InputLabel for="value" value="Value" />

                        <TextInput
                            id="value"
                            ref={startTimeInput}
                            value={data.value}
                            handleChange={(event) => setData('value', event.target.value)}
                            type="number"
                            className="mt-1 block w-full"
                        />


                        <InputError message={errors.duration} className="mt-2" />
                    </div>
                )}

                <div className="flex items-center gap-4">
                    <PrimaryButton processing={processing}>Save</PrimaryButton>

                    <Transition
                        show={recentlySuccessful}
                        enterFrom="opacity-0"
                        leaveTo="opacity-0"
                        className="transition ease-in-out"
                    >
                        <p className="text-sm text-gray-600 dark:text-gray-400">Saved.</p>
                    </Transition>
                </div>

            </form>

        </>
    )
}

export default EditHabitTimeForm
