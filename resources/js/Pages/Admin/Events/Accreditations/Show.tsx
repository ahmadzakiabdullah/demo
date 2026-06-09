import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ArrowLeft, FileBadge, Printer } from 'lucide-react';

export default function Show({ event, accreditation, qrSvg }: any) {
    return (
        <AdminLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Perincian Akreditasi</h2>}
        >
            <Head title={`Akreditasi - ${accreditation.qr_code}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-4">
                        <Button variant="ghost" asChild>
                            <Link href={route('admin.events.accreditations.index', event.id)}>
                                <ArrowLeft className="w-4 h-4 mr-2" /> Kembali ke Senarai
                            </Link>
                        </Button>
                    </div>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-2xl">{accreditation.accreditable?.name}</CardTitle>
                                <CardDescription>ID Sistem: {accreditation.qr_code}</CardDescription>
                            </div>
                            <Badge variant={accreditation.status === 'active' ? 'default' : 'destructive'} className="text-lg py-1 px-3">
                                {accreditation.status.toUpperCase()}
                            </Badge>
                        </CardHeader>
                        <Separator />
                        <CardContent className="pt-6 flex flex-col md:flex-row gap-8">
                            
                            {/* Ruangan Info */}
                            <div className="flex-1 space-y-4">
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Jenis Pas</p>
                                    <p className="text-lg capitalize font-semibold">{accreditation.type}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Pasukan / Kontinjen</p>
                                    <p className="text-lg">{accreditation.event_participant?.name || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Tarikh Dikeluarkan</p>
                                    <p className="text-lg">{new Date(accreditation.issued_at).toLocaleDateString('ms-MY')}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-500">Nota Tambahan</p>
                                    <p className="text-md text-gray-700">{accreditation.notes || 'Tiada nota.'}</p>
                                </div>
                            </div>

                            {/* Ruangan QR Code & Butang */}
                            <div className="flex flex-col items-center justify-center bg-gray-50 p-6 rounded-lg border">
                                <div 
                                    className="bg-white p-4 rounded-xl shadow-sm mb-6"
                                    dangerouslySetInnerHTML={{ __html: qrSvg }}
                                />
                                <div className="space-y-3 w-full">
                                    <Button className="w-full" asChild>
                                        <a href={route('admin.events.accreditations.badge', [event.id, accreditation.id])} target="_blank">
                                            <FileBadge className="w-4 h-4 mr-2" /> Muat Turun Badge (PDF)
                                        </a>
                                    </Button>
                                    <Button variant="outline" className="w-full">
                                        <Printer className="w-4 h-4 mr-2" /> Cetak Terus (BETA)
                                    </Button>
                                </div>
                            </div>

                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}