declare module '@wordpress/server-side-render' {
    import { ComponentType } from 'react';

    export interface ServerSideRenderProps {
        block?: string;
        attributes?: any;
        LoadingResponsePlaceholder?: any;
        EmptyResponsePlaceholder?: any;
    }
    
    // Changed to default export
    const ServerSideRender: ComponentType<ServerSideRenderProps>;
    export default ServerSideRender;
}
