import Component from 'ShopUi/models/component';

declare const window: any;


export default class ConfirmationButton extends Component {
    protected button: HTMLElement;
    protected xhr: XMLHttpRequest;

    constructor() {
        super();
        this.xhr = new XMLHttpRequest();
    }

    protected readyCallback(): void {
        this.button = this.querySelector(`.${this.jsName}__button`);
        this.mapEvents();
    }

    protected mapEvents(): void {
        this.button.addEventListener('click', this.initConfirmation.bind(this));
    }

    protected placeOrder<T = string>(confirmationFlow): Promise<T> {
         return new Promise((resolve, reject) => {
            this.xhr.open('GET', this.url);
            this.xhr.addEventListener('load', (event: Event) => this.onRequestLoad(resolve, reject, confirmationFlow));
            this.xhr.addEventListener('error', (event: Event) => this.onRequestError(reject, confirmationFlow));
            this.xhr.send();
        })

    }

    protected onRequestLoad(resolve, reject, confirmationFlow): void {
        if(this.xhr.status === 200){
            confirmationFlow.success();
            resolve(this.xhr.response);
        }
    }

    protected onRequestError(reject, confirmationFlow): void {
        confirmationFlow.error();
        reject(new Error(`${this.url} request aborted`));
    }

    protected initConfirmation (event: Event) {
        event.preventDefault();
        window.OffAmazonPayments.initConfirmationFlow(this.sellerId, this.orderReferenceId, (confirmationFlow) => {
            this.placeOrder(confirmationFlow);
        })

    }

    get url ():string {
        return this.getAttribute('url');
    }

    get sellerId (): string {
        return this.getAttribute('seller-id');
    }

    get orderReferenceId (): string {
        return this.getAttribute('order-reference-id');
    }

}
