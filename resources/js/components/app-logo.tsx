import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center">
                <AppLogoIcon className="size-8" />
            </div>
            <div className="grid flex-1 text-left text-lg text-shadow-lg/10 dark:text-shadow-white">
                <span className="truncate leading-tight font-bold">
                    mrifqyabdallah
                </span>
            </div>
        </>
    );
}
