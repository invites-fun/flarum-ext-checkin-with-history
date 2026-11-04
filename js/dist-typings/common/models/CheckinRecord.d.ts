import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
export default class CheckinRecord extends Model {
    date: () => string;
    color: () => string;
    type: () => number;
    streak: () => number;
    checkedInAt: () => Date | null | undefined;
    user: () => false | User;
}
