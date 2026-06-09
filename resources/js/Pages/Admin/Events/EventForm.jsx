import InputError from '@/Components/InputError';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Link } from '@inertiajs/react';

export default function EventForm({
    data,
    setData,
    errors,
    processing,
    organizations = [],
    eventTypes,
    eventCategories,
    statuses,
    allowedTransitions = null,
    cadences = [],
    participantUnitLabels = [],
    eventSeries = [],
    onSubmit,
    submitLabel,
    isEdit = false,
}) {
    const statusOptions = allowedTransitions
        ? [data.status, ...allowedTransitions.filter((s) => s !== data.status)]
        : statuses;

    return (
        <form onSubmit={onSubmit} className="max-w-2xl space-y-6">
            {!isEdit && organizations.length > 0 && (
                <div className="space-y-2">
                    <Label htmlFor="organization_id">Organization</Label>
                    <Select
                        value={String(data.organization_id ?? '')}
                        onValueChange={(value) =>
                            setData('organization_id', Number(value))
                        }
                    >
                        <SelectTrigger id="organization_id" className="w-full">
                            <SelectValue placeholder="Select organization" />
                        </SelectTrigger>
                        <SelectContent>
                            {organizations.map((organization) => (
                                <SelectItem
                                    key={organization.id}
                                    value={String(organization.id)}
                                >
                                    {organization.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.organization_id} />
                </div>
            )}

            <div className="space-y-2">
                <Label htmlFor="name">Event Name</Label>
                <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    autoFocus
                    aria-invalid={!!errors.name}
                />
                <InputError message={errors.name} />
            </div>

            <div className="space-y-2">
                <Label htmlFor="slug">
                    Slug{!isEdit && ' (optional — auto-generated from name)'}
                </Label>
                <Input
                    id="slug"
                    value={data.slug}
                    onChange={(e) => setData('slug', e.target.value)}
                    aria-invalid={!!errors.slug}
                />
                <InputError message={errors.slug} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="edition_year">Edition Year</Label>
                    <Input
                        id="edition_year"
                        type="number"
                        min="2000"
                        max="2100"
                        value={data.edition_year ?? ''}
                        onChange={(e) =>
                            setData('edition_year', Number(e.target.value))
                        }
                        aria-invalid={!!errors.edition_year}
                    />
                    <InputError message={errors.edition_year} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="cadence">Cadence</Label>
                    <Select
                        value={data.cadence || ''}
                        onValueChange={(value) =>
                            setData('cadence', value || null)
                        }
                    >
                        <SelectTrigger id="cadence" className="w-full">
                            <SelectValue placeholder="Select cadence" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">Not set</SelectItem>
                            {cadences.map((cadence) => (
                                <SelectItem key={cadence} value={cadence}>
                                    {cadence}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.cadence} />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="participant_unit_label">Participant Label</Label>
                    <Select
                        value={data.participant_unit_label || ''}
                        onValueChange={(value) =>
                            setData('participant_unit_label', value || null)
                        }
                    >
                        <SelectTrigger
                            id="participant_unit_label"
                            className="w-full"
                        >
                            <SelectValue placeholder="Select label" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">Not set</SelectItem>
                            {participantUnitLabels.map((label) => (
                                <SelectItem key={label} value={label}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.participant_unit_label} />
                </div>

                {isEdit && eventSeries.length > 0 && (
                    <div className="space-y-2">
                        <Label htmlFor="event_series_id">Event Series</Label>
                        <Select
                            value={
                                data.event_series_id
                                    ? String(data.event_series_id)
                                    : ''
                            }
                            onValueChange={(value) =>
                                setData(
                                    'event_series_id',
                                    value ? Number(value) : null,
                                )
                            }
                        >
                            <SelectTrigger id="event_series_id" className="w-full">
                                <SelectValue placeholder="None" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">None</SelectItem>
                                {eventSeries.map((series) => (
                                    <SelectItem
                                        key={series.id}
                                        value={String(series.id)}
                                    >
                                        {series.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.event_series_id} />
                    </div>
                )}
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="event_type_id">Event Type</Label>
                    <Select
                        value={String(data.event_type_id ?? '')}
                        onValueChange={(value) =>
                            setData('event_type_id', Number(value))
                        }
                    >
                        <SelectTrigger id="event_type_id" className="w-full">
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            {eventTypes.map((type) => (
                                <SelectItem key={type.id} value={String(type.id)}>
                                    {type.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.event_type_id} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="event_category_id">Category</Label>
                    <Select
                        value={String(data.event_category_id ?? '')}
                        onValueChange={(value) =>
                            setData('event_category_id', Number(value))
                        }
                    >
                        <SelectTrigger id="event_category_id" className="w-full">
                            <SelectValue placeholder="Select category" />
                        </SelectTrigger>
                        <SelectContent>
                            {eventCategories.map((category) => (
                                <SelectItem
                                    key={category.id}
                                    value={String(category.id)}
                                >
                                    {category.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.event_category_id} />
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="location">Location</Label>
                <Input
                    id="location"
                    value={data.location}
                    onChange={(e) => setData('location', e.target.value)}
                    aria-invalid={!!errors.location}
                />
                <InputError message={errors.location} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="starts_at">Starts At</Label>
                    <Input
                        id="starts_at"
                        type="datetime-local"
                        value={data.starts_at ?? ''}
                        onChange={(e) => setData('starts_at', e.target.value)}
                        aria-invalid={!!errors.starts_at}
                    />
                    <InputError message={errors.starts_at} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="ends_at">Ends At</Label>
                    <Input
                        id="ends_at"
                        type="datetime-local"
                        value={data.ends_at ?? ''}
                        onChange={(e) => setData('ends_at', e.target.value)}
                        aria-invalid={!!errors.ends_at}
                    />
                    <InputError message={errors.ends_at} />
                </div>
            </div>

            <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <textarea
                    id="description"
                    value={data.description ?? ''}
                    onChange={(e) => setData('description', e.target.value)}
                    rows={4}
                    className="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    aria-invalid={!!errors.description}
                />
                <InputError message={errors.description} />
            </div>

            <div className="space-y-2">
                <Label htmlFor="status">Status</Label>
                <Select
                    value={data.status}
                    onValueChange={(value) => setData('status', value)}
                >
                    <SelectTrigger id="status" className="w-full">
                        <SelectValue placeholder="Select status" />
                    </SelectTrigger>
                    <SelectContent>
                        {statusOptions.map((status) => (
                            <SelectItem key={status} value={status}>
                                {status}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.status} />
            </div>

            <div className="flex items-center gap-4">
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>
                <Button
                    variant="outline"
                    render={
                        <Link
                            href={
                                isEdit
                                    ? route('admin.events.show', data.id)
                                    : route('admin.events.index')
                            }
                        />
                    }
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}