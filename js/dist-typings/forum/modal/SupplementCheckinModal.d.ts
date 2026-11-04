import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
export interface SupplementCheckinModalAttrs extends IInternalModalAttrs {
    date: string;
    callback?: () => void;
}
export default class SupplementCheckinModal extends Modal<SupplementCheckinModalAttrs> {
    loading: boolean;
    title(): Mithril.Children;
    className(): string;
    content(): Mithril.Children;
    onsubmit(e: Event): Promise<void>;
}
