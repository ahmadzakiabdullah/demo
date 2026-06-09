import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Plus, Eye, FileBadge } from 'lucide-react';

interface Accreditation {
    id: number;
    type: string;
    status: string;
    qr_code: string;
    accreditable?: { id: number; name: string };
    event_participant?: { id: number; name: string };
}

export default function Index({ event, accreditations, participants }: any) {
    return (
        <AdminLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Accreditations - {event.name}</h2>}
        >
            <Head title={`Accreditations - ${event.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center mb-6">
                        <div>
                            <h3 className="text-lg font-medium">Senarai Akreditasi</h3>
                            <p className="text-sm text-gray-500">Urus pas ID peserta dan pegawai.</p>
                        </div>
                        <Button asChild>
                            <Link href={route('admin.events.accreditations.create', event.id)}>
                                <Plus className="w-4 h-4 mr-2" /> Daftar Akreditasi
                            </Link>
                        </Button>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Kod QR</TableHead>
                                    <TableHead>Penerima</TableHead>
                                    <TableHead>Jenis Pas</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Tindakan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {accreditations.map((acc: Accreditation) => (
                                    <TableRow key={acc.id}>
                                        <TableCell className="font-medium text-blue-600">{acc.qr_code}</TableCell>
                                        <TableCell>
                                            {acc.accreditable?.name || 'Tiada Data'}
                                            <div className="text-xs text-gray-500 mt-1">
                                                {acc.event_participant?.name}
                                            </div>
                                        </TableCell>
                                        <TableCell className="capitalize">{acc.type}</TableCell>
                                        <TableCell>
                                            <Badge variant={acc.status === 'active' ? 'default' : 'secondary'}>
                                                {acc.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right space-x-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route('admin.events.accreditations.show', [event.id, acc.id])}>
                                                    <Eye className="w-4 h-4 mr-1" /> Butiran
                                                </Link>
                                            </Button>
                                            <Button variant="secondary" size="sm" asChild title="Muat Turun Badge PDF">
                                                <a href={route('admin.events.accreditations.badge', [event.id, acc.id])} target="_blank">
                                                    <FileBadge className="w-4 h-4 mr-1" /> Badge
                                                </a>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}