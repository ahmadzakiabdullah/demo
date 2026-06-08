import GuestLayout from '@/Layouts/GuestLayout';
import { Button } from '@/components/ui/button';
import { Head, Link, useForm } from '@inertiajs/react';

export default function VerifyEmail({ status }) {
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Email Verification" />

            <p className="mb-4 text-sm text-muted-foreground">
                Thanks for signing up! Before getting started, could you verify
                your email address by clicking on the link we just emailed to
                you? If you didn't receive the email, we will gladly send you
                another.
            </p>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    A new verification link has been sent to the email address
                    you provided during registration.
                </div>
            )}

            <form onSubmit={submit}>
                <div className="flex items-center justify-between gap-4">
                    <Button type="submit" disabled={processing}>
                        Resend Verification Email
                    </Button>

                    <Button
                        variant="ghost"
                        render={
                            <Link href={route('logout')} method="post" as="button" />
                        }
                    >
                        Log Out
                    </Button>
                </div>
            </form>
        </GuestLayout>
    );
}