import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import Card from "@/Components/Card";

export default function Dashboard() {
    return (
        <AuthenticatedLayout title="Panel">
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <Card>¡Te damos la bienvenida a Padiush!</Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
