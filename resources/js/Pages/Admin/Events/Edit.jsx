import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import EventForm from '@/Pages/Admin/Events/EventForm';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, useForm } from '@inertiajs/react';

export default function Edit({
    event,
    eventTypes,
    eventCategories,
    statuses,
    cadences,
    participantUnitLabels,
    eventSeries,
    allowedTransitions,
}) {
    const { data, setData, put, processing, errors } = useForm({
        id: event.id,
        event_type_id: event.event_type_id,
        event_category_id: event.event_category_id,
        event_series_id: event.event_series_id ?? null,
        name: event.name,
        slug: event.slug,
        edition_year: event.edition_year,
        cadence: event.cadence ?? '',
        participant_unit_label: event.participant_unit_label ?? '',
        status: event.status,
        location: event.location ?? '',
        description: event.description ?? '',
        starts_at: event.starts_at ?? '',
        ends_at: event.ends_at ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.events.update', event.id));
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Edit' },
            ]}
            header={
                <h2 className="text-xl font-semibold leading-tight text-foreground">
                    Edit Event
                </h2>
            }
        >
            <Head title={`Edit ${event.name}`} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <CardTitle>{event.name}</CardTitle>
                            <p className="text-sm text-muted-foreground">
                                {event.organization?.name}
                            </p>
                        </CardHeader>
                        <CardContent>
                            <EventForm
                                data={data}
                                setData={setData}
                                errors={errors}
                                processing={processing}
                                eventTypes={eventTypes}
                                eventCategories={eventCategories}
                                statuses={statuses}
                                cadences={cadences}
                                participantUnitLabels={participantUnitLabels}
                                eventSeries={eventSeries}
                                allowedTransitions={allowedTransitions}
                                onSubmit={submit}
                                submitLabel="Save Changes"
                                isEdit
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}