import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import EventForm from '@/Pages/Admin/Events/EventForm';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, useForm } from '@inertiajs/react';

export default function Create({
    organizations,
    eventTypes,
    eventCategories,
    statuses,
    defaultOrganizationId,
}) {
    const { data, setData, post, processing, errors } = useForm({
        organization_id: defaultOrganizationId ?? organizations[0]?.id ?? '',
        event_type_id: eventTypes[0]?.id ?? '',
        event_category_id: eventCategories[0]?.id ?? '',
        name: '',
        slug: '',
        status: 'draft',
        location: '',
        description: '',
        starts_at: '',
        ends_at: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.events.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Create Event
                </h2>
            }
        >
            <Head title="Create Event" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>New Event</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <EventForm
                                data={data}
                                setData={setData}
                                errors={errors}
                                processing={processing}
                                organizations={organizations}
                                eventTypes={eventTypes}
                                eventCategories={eventCategories}
                                statuses={statuses}
                                onSubmit={submit}
                                submitLabel="Create Event"
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}