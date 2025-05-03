import ApplicationLogo from "@/Components/ApplicationLogo";
import { Link, Head, usePage } from "@inertiajs/react";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faRightFromBracket } from "@fortawesome/pro-regular-svg-icons";

export default function AuthenticatedLayout({ children, title }) {
    const { auth } = usePage().props;

    return (
        <div className="flex flex-col w-full z-10">
            <Head title={title} />
            <div className="navbar bg-primary text-primary-content shadow-sm h-16">
                <div className="navbar-start">
                    <div className="dropdown">
                        <div
                            tabIndex={0}
                            role="button"
                            className="btn btn-ghost lg:hidden"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                {" "}
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    d="M4 6h16M4 12h8m-8 6h16"
                                />{" "}
                            </svg>
                        </div>
                        <ul
                            tabIndex={0}
                            className="menu menu-sm dropdown-content bg-base-100 rounded-box z-1 mt-3 w-52 p-2 shadow"
                        >
                            <li>
                                <a>Item 1</a>
                            </li>
                            <li>
                                <a>Parent</a>
                                <ul className="p-2">
                                    <li>
                                        <a>Submenu 1</a>
                                    </li>
                                    <li>
                                        <a>Submenu 2</a>
                                    </li>
                                </ul>
                            </li>
                            <li>
                                <a>Item 3</a>
                            </li>
                        </ul>
                    </div>
                    <Link
                        className="btn btn-ghost text-xl"
                        href={route("dashboard")}
                    >
                        <ApplicationLogo className="h-10 w-auto fill-current" />
                    </Link>
                </div>
                <div className="navbar-center hidden lg:flex">
                    <ul className="menu menu-horizontal px-1">
                        <li>
                            <a href={route("projects.index")}>Proyectos</a>
                        </li>
                        {auth.projects >= 0 && (
                            <>
                                <li>
                                    <details>
                                        <summary>Entrevistas</summary>
                                        <ul className="p-2 bg-base-200 z-20 shadow-xl">
                                            <li>
                                                <Link
                                                    href={route(
                                                        "designer.index"
                                                    )}
                                                >
                                                    Diseñar
                                                </Link>
                                            </li>
                                            <li>
                                                <a
                                                    href={route(
                                                        "interviews.index"
                                                    )}
                                                >
                                                    Entrevistar
                                                </a>
                                            </li>
                                        </ul>
                                    </details>
                                </li>
                                <li>
                                    <a href={route("catalogs.index")}>
                                        Catálogos
                                    </a>
                                </li>
                                <li>
                                    <a href={route("data.index")}>Datos</a>
                                </li>
                            </>
                        )}
                    </ul>
                </div>
                <div className="navbar-end">
                    <Link
                        className="btn btn-ghost"
                        href="/logout"
                        method="post"
                        as="button"
                    >
                        <FontAwesomeIcon icon={faRightFromBracket} />
                    </Link>
                </div>
            </div>

            <div className="bg-base-200 px-4 md:px-12 lg:px-24 py-4 md:py-6 text-lg md:text-xl lg:text-2xl font-semibold">
                {title}
            </div>

            <div className="flex-grow z-0">{children}</div>
        </div>
    );
}
