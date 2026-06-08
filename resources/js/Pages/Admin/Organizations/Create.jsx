import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import OrganizationForm from '@/Pages/Admin/Organizations/OrganizationForm';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, useForm } from '@inertiajs/react';

export default function Create({
    types,
    statuses,
    defaultTimezone,
    defaultLocale,
}) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        type: 'university',
        timezone: defaultTimezone,
        locale: defaultLocale,
        status: 'active',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.organizations.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Create Organization
                </h2>
            }
        >
            <Head title="Create Organization" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>New Organization</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <OrganizationForm
                                data={data}
                                setData={setData}
                                errors={errors}
                                processing={processing}
                                types={types}
                                statuses={statuses}
                                onSubmit={submit}
                                submitLabel="Create Organization"
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}