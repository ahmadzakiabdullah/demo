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

export default function OrganizationForm({
    data,
    setData,
    errors,
    processing,
    types,
    statuses,
    onSubmit,
    submitLabel,
    isEdit = false,
}) {
    return (
        <form onSubmit={onSubmit} className="max-w-xl space-y-6">
            <div className="space-y-2">
                <Label htmlFor="name">Name</Label>
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
                    placeholder={isEdit ? undefined : 'e.g. utem'}
                    aria-invalid={!!errors.slug}
                />
                <InputError message={errors.slug} />
            </div>

            <div className="space-y-2">
                <Label htmlFor="type">Type</Label>
                <Select
                    value={data.type}
                    onValueChange={(value) => setData('type', value)}
                >
                    <SelectTrigger id="type" className="w-full">
                        <SelectValue placeholder="Select type" />
                    </SelectTrigger>
                    <SelectContent>
                        {types.map((type) => (
                            <SelectItem key={type} value={type}>
                                {type}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.type} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                    <Label htmlFor="timezone">Timezone</Label>
                    <Input
                        id="timezone"
                        value={data.timezone}
                        onChange={(e) => setData('timezone', e.target.value)}
                        aria-invalid={!!errors.timezone}
                    />
                    <InputError message={errors.timezone} />
                </div>
                <div className="space-y-2">
                    <Label htmlFor="locale">Locale</Label>
                    <Input
                        id="locale"
                        value={data.locale}
                        onChange={(e) => setData('locale', e.target.value)}
                        aria-invalid={!!errors.locale}
                    />
                    <InputError message={errors.locale} />
                </div>
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
                        {statuses.map((status) => (
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
                    render={<Link href={route('admin.organizations.index')} />}
                >
                    Cancel
                </Button>
            </div>
        </form>
    );
}