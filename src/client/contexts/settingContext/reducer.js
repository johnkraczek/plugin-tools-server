
export const reducer = (state, action) => {
    switch (action.type) {
        case "WEBHOOK-URL":
            return {...state, webhook_url: action.value};
        case "WEBHOOK-AUTH":
            return {...state, webhook_auth: action.value};
        case "INITIALIZE":
            return action.value;
        default:
            return state;
    }
};

export const initialFormData =
{
    webhook_url: "",
    webhook_auth: ""
}
