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

export default function UserForm({
    data,
    setData,
    errors,
    processing,
    roles,
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
                <Label htmlFor="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    aria-invalid={!!errors.email}
                />
                <InputError message={errors.email} />
            </div>

            <div className="space-y-2">
                <Label htmlFor="system_role">System Role</Label>
                <Select
                    value={data.system_role ?? ''}
                    onValueChange={(value) =>
                        setData('system_role', value === 'member' ? '' : value)
                    }
                >
                    <SelectTrigger id="system_role" className="w-full">
                        <SelectValue placeholder="Select role" />
                    </SelectTrigger>
                    <SelectContent>
                        {roles.map((role) => (
                            <SelectItem
                                key={role.slug || 'member'}
                                value={role.slug || 'member'}
                            >
                                {role.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.system_role} />
            </div>

            <div className="space-y-2">
                <Label htmlFor="password">
                    Password{isEdit && ' (leave blank to keep current)'}
                </Label>
                <Input
                    id="password"
                    type="password"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    autoComplete="new-password"
                    aria-invalid={!!errors.password}
                />
                <InputError message={errors.password} />
            </div>

            {!isEdit && (
                <div className="space-y-2">
                    <Label htmlFor="password_confirmation">Confirm Password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        autoComplete="new-password"
                        aria-invalid={!!errors.password_confirmation}
                    />
                    <InputError message={errors.password_confirmation} />
                </div>
            )}

            {isEdit && data.password && (
                <div className="space-y-2">
                    <Label htmlFor="password_confirmation">Confirm Password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        autoComplete="new-password"
                        aria-invalid={!!errors.password_confirmation}
                    />
                    <InputError message={errors.password_confirmation} />
                </div>
            )}

            <div className="flex items-center gap-4">
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>
                <Button variant="outline" render={<Link href={route('admin.users.index')} />}>
                    Cancel
                </Button>
            </div>
        </form>
    );
}