import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { FaArrowLeft } from 'react-icons/fa';
import AddTransactionForm from './partials/AddTransactionForm';
import { getTextColor } from '@/Helpers/Colors';

function AddTransaction(props) {
    const { habitUser } = props;
    const textColor = getTextColor(habitUser.color_index);

    return (
        <AuthenticatedLayout
            auth={props.auth}
            errors={props.errors}
            header={
                <div className="flex items-center gap-4">
                    <Link href={route('habits.index')} className="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                        <FaArrowLeft size={20} />
                    </Link>
                    <div className="flex items-center gap-3">
                        <span className="text-3xl">{habitUser.icon}</span>
                        <h2 className="font-semibold text-xl leading-tight" style={{ color: textColor }}>
                            {habitUser.name}
                        </h2>
                    </div>
                </div>
            }
        >
            <Head title={`Add ${habitUser.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
                        <AddTransactionForm habitUser={habitUser} popularTags={props.popularTags || []} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

export default AddTransaction;
