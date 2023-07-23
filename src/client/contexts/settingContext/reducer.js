
export const reducer = (state, action) => {
    switch (action.type) {
        case "USERNAME":
            return {...state, bitbucket_username: action.value};
        case "PASSWORD":
            if (state.hasOwnProperty('password_blurred') && !state.password_blurred){
                return {...state, bitbucket_password: action.value.slice(-1), password_blurred: true};
            }
            return {...state, bitbucket_password: action.value};
        case "WORKSPACE":
            return {...state, bitbucket_workspace: action.value};
        case "INITIALIZE":
            return {...state, ...action.value}
        default:
            return state;
    }
};

export const initialFormData =
{
    bitbucket_username: "",
    bitbucket_password: "",
    password_blurred: false,
    bitbucket_workspace: ""
}
