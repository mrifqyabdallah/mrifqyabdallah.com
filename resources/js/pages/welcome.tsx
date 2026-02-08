import { Head, Link, usePage } from '@inertiajs/react';
import { ExternalLinkIcon, GithubIcon, LinkedinIcon } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import AppearanceToggleTab from '@/components/appearance-tabs';
import { Button } from '@/components/ui/button';
import type { SharedData } from '@/types';
import { dashboard, login, register } from '@/routes';

type Website = {
    title: string;
    description?: string | null;
    url: string;
};

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;
    const websites: Website[] = [
        {
            title: 'European Mint',
            url: 'https://europeanmint.com/',
        },
        {
            title: 'API FKIP UNS',
            url: 'https://api.fkip.uns.ac.id/',
        },
        {
            title: 'Layanan FKIP UNS',
            url: 'https://layanan.fkip.uns.ac.id/',
        },
        {
            title: 'Calon Guru PPG FKIP UNS',
            url: 'https://cagur.fkip.uns.ac.id/',
        },
        {
            title: 'Valid PPG FKIP UNS',
            url: 'https://valid.fkip.uns.ac.id/',
        },
    ];

    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>
            <div className="relative min-h-screen w-full bg-[#FDFDFC] text-[#1b1b18] lg:justify-center dark:bg-[#0a0a0a]">
                <div
                    className="pointer-events-none absolute inset-0 z-0"
                    style={{
                        backgroundImage: `
        repeating-linear-gradient(0deg, transparent, transparent 5px, rgba(75, 85, 99, 0.06) 5px, rgba(75, 85, 99, 0.06) 6px, transparent 6px, transparent 15px),
        repeating-linear-gradient(90deg, transparent, transparent 5px, rgba(75, 85, 99, 0.06) 5px, rgba(75, 85, 99, 0.06) 6px, transparent 6px, transparent 15px),
        repeating-linear-gradient(0deg, transparent, transparent 10px, rgba(107, 114, 128, 0.04) 10px, rgba(107, 114, 128, 0.04) 11px, transparent 11px, transparent 30px),
        repeating-linear-gradient(90deg, transparent, transparent 10px, rgba(107, 114, 128, 0.04) 10px, rgba(107, 114, 128, 0.04) 11px, transparent 11px, transparent 30px)
      `,
                    }}
                />
                <div className="relative z-1 min-h-screen opacity-100 transition-opacity duration-1000 starting:opacity-0">
                    <div className="flex min-h-screen flex-col items-center p-6 text-[#1b1b18] lg:justify-center lg:p-8">
                        <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
                            <nav className="flex items-center justify-end gap-4">
                                {auth.user ? (
                                    <Link
                                        href={dashboard()}
                                        className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={login()}
                                            className="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                                        >
                                            Log in
                                        </Link>
                                        {canRegister && (
                                            <Link
                                                href={register()}
                                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                            >
                                                Register
                                            </Link>
                                        )}
                                    </>
                                )}
                            </nav>
                        </header>
                        <div className="flex w-full flex-col items-center justify-center">
                            <div className="flex scale-125 items-center justify-center gap-x-2 p-8 dark:text-white">
                                <AppLogo />
                            </div>
                            <div className="flex w-full items-center justify-center lg:grow">
                                <main className="relative z-10 flex w-full flex-col md:max-w-2/3 lg:max-w-4xl lg:flex-row">
                                    <div className="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                                        <h1 className="mb-1 font-medium">
                                            Greetings, stranger!
                                        </h1>
                                        <p className="mb-2 text-[#706f6c] dark:text-[#A1A09A]">
                                            I'm Rifqy Abdallah, a fullstack web
                                            developer focused on PHP/Laravel,
                                            with a passion for exploring other
                                            technologies.
                                        </p>
                                        <p className="mb-2 text-[#706f6c] dark:text-[#A1A09A]">
                                            If you'd like to offer me some
                                            opportunities, I would love to hear
                                            from you. Email me at
                                            <a
                                                href="mailto:mrifqyabdallah@gmail.com"
                                                target="_blank"
                                                className="ml-1 inline-flex items-center space-x-1 font-medium break-all text-[#f53003] underline underline-offset-4 dark:text-[#FF4433]"
                                            >
                                                mrifqyabdallah@gmail.com
                                            </a>
                                            .
                                        </p>
                                        <ul className="mt-7 flex gap-3 text-sm leading-normal">
                                            <li>
                                                <Button asChild size="icon-lg">
                                                    <Link href="https://www.linkedin.com/in/mrifqyabdallah/">
                                                        <LinkedinIcon />
                                                    </Link>
                                                </Button>
                                            </li>
                                            <li>
                                                <Button asChild size="icon-lg">
                                                    <Link href="https://github.com/mrifqyabdallah/">
                                                        <GithubIcon />
                                                    </Link>
                                                </Button>
                                            </li>
                                        </ul>
                                    </div>
                                    <div className="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">
                                        <p className="mb-2 text-[#706f6c] dark:text-[#A1A09A]">
                                            Some hand-made of mine:
                                        </p>
                                        <ul>
                                            {websites.map(
                                                (website: Website) => (
                                                    <>
                                                        <li className="relative flex items-center py-1">
                                                            <span className="relative bg-white py-1 dark:bg-[#161615]">
                                                                <span className="flex h-3.5 w-3.5 items-center justify-center rounded-full border border-[#e3e3e0] bg-[#FDFDFC] shadow-[0px_0px_1px_0px_rgba(0,0,0,0.03),0px_1px_2px_0px_rgba(0,0,0,0.06)] dark:border-[#3E3E3A] dark:bg-[#161615]">
                                                                    <span className="h-1.5 w-1.5 rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A]" />
                                                                </span>
                                                            </span>
                                                            <span>
                                                                <Button
                                                                    asChild
                                                                    variant="link"
                                                                >
                                                                    <Link
                                                                        href={
                                                                            website.url
                                                                        }
                                                                    >
                                                                        {
                                                                            website.title
                                                                        }
                                                                        <ExternalLinkIcon />
                                                                    </Link>
                                                                </Button>
                                                            </span>
                                                        </li>
                                                    </>
                                                ),
                                            )}
                                        </ul>
                                    </div>
                                </main>
                            </div>
                        </div>
                        <div className="flex scale-100 flex-col items-center justify-center gap-y-6 pt-8 dark:text-white">
                            <AppearanceToggleTab />

                            <Button variant="ghost" asChild>
                                <Link href="https://github.com/mrifqyabdallah/mrifqyabdallah.com">
                                    <GithubIcon />
                                    View source code
                                </Link>
                            </Button>
                        </div>
                        <div className="hidden h-14.5 lg:block"></div>
                    </div>
                </div>
            </div>
        </>
    );
}
