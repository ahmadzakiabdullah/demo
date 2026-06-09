import AdminLayout from '@/Layouts/AdminLayout';

export default function AuthenticatedLayout({ event, ...props }) {
    return <AdminLayout event={event} {...props} />;
}