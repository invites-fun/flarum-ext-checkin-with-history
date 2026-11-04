/// <reference types="mithril" />
/// <reference types="flarum/@types/translator-icu-rich" />
import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
interface CheckinResultModalAttrs extends IInternalModalAttrs {
    success: boolean;
}
export default class CheckinResultModal extends Modal<CheckinResultModalAttrs> {
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
}
export {};
