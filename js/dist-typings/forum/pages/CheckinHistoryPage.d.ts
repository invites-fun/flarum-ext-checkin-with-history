import UserPage from 'flarum/forum/components/UserPage';
import type Mithril from 'mithril';
import CheckinRecord from '../../common/models/CheckinRecord';
export default class CheckinHistoryPage extends UserPage {
    calendar: any;
    removed: boolean;
    records: CheckinRecord[];
    loadingHistory: boolean;
    oninit(vnode: Mithril.Vnode<any, this>): void;
    canViewHistory(): boolean;
    content(): Mithril.Children;
    onremove(vnode: Mithril.VnodeDOM<any, this>): void;
    getData(info: {
        startStr: string;
        endStr: string;
    }): Promise<any[]>;
    renderCalendar(element?: HTMLElement): Promise<void>;
    openCreateModal(info: {
        dateStr: string;
    }): Promise<boolean | void>;
}
