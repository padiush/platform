import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import Card from "@/Components/Card";
import { Link } from "@inertiajs/react";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faCheck, faTimes } from "@fortawesome/pro-regular-svg-icons";

export default function Index({ projects }) {
    return (
        <AuthenticatedLayout title="Diseñador de entrevistas">
            <div className="p-4 md:pt-8 lg:pt-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-4">
                        {projects.map((project) => (
                            <Card key={project.id} title={project.name}>
                                <div className="overflow-x-auto">
                                    <table className="table">
                                        <thead>
                                            <tr>
                                                <th>Nombre del formulario</th>
                                                <th className="text-center">
                                                    ¿Habilitado?
                                                </th>
                                                <th className="text-center">
                                                    Entrevistas realizadas
                                                </th>
                                                <th className="text-center">
                                                    Acciones
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {project.interview_forms.map(
                                                (form) => (
                                                    <tr key={form.id}>
                                                        <td>{form.name}</td>
                                                        <td className="text-center">
                                                            {form.enabled ? (
                                                                <FontAwesomeIcon
                                                                    icon={
                                                                        faCheck
                                                                    }
                                                                />
                                                            ) : (
                                                                <FontAwesomeIcon
                                                                    icon={
                                                                        faTimes
                                                                    }
                                                                />
                                                            )}
                                                        </td>
                                                        <td className="text-center">
                                                            {
                                                                form.instances
                                                                    .length
                                                            }
                                                        </td>
                                                        <td className="text-center">
                                                            <a
                                                                className="btn btn-primary btn-xs"
                                                                href={route(
                                                                    "designer.form.edit",
                                                                    {
                                                                        project:
                                                                            project.id,
                                                                        form: form.id,
                                                                    }
                                                                )}
                                                            >
                                                                Editar
                                                            </a>
                                                        </td>
                                                    </tr>
                                                )
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </Card>
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
