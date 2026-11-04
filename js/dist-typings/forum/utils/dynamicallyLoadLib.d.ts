export interface ResourceConfig {
    url?: string;
    sri?: string;
}
export interface LibItem {
    url: string;
    integrity?: string;
    crossOrigin?: string;
}
export interface LibConfig {
    js: LibItem | LibItem[];
    loaded: () => boolean;
}
export default function dynamicallyLoadLib(lib: string): Promise<void>;
