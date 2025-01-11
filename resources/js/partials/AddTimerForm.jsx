import React from 'react'
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import SelectOptions from '@/Components/SelectOptions';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Transition } from '@headlessui/react';
import { useEffect, useRef } from 'react';


function AddTimerForm(props) {
    const { data, setData, errors, post, reset, processing, recentlySuccessful } = useForm({
        habit_id: props.habits[0].value,
    });


    function onSubmit(event) {
        event.preventDefault();
        post(route('habits.transactions.timer.store'), {
            preserveScroll: true,
            onError: () => {
                if (errors.start_time) {
                    reset();
                }
            },
        });
    }

    return (
        <>
            <section className={`space-y-6`}>
                <header>
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">Start Habit Timer</h2>

                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Sometimes you gotta fix habit times and this is the place to do it.
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

                    <InputError message={errors.habit_id} className="mt-2" />
                </div>

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

export default AddTimerForm