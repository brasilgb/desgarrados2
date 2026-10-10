import { Link } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex items-center gap-2 rounded font-medium focus-visible:outline-2 focus-visible:outline-offset-4"
                        >
                            <MapPin className="size-7" aria-hidden="true" />
                            <span className="text-2xl font-semibold tracking-tight">
                                desgarrados
                                <span className="text-[#9d4934] dark:text-[#e3a089]">
                                    .
                                </span>
                            </span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {description}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
