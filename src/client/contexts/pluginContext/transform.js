// given a download of data transform it into the plugin format.

const transform = (data) => {
    let transformedData = {}

    // loop through all of the installed plugins. 
    let count = 0;
    for (const plugin in data.plugins) {

        let blurred = false;
        let composerSlug;

        if (data.ydtb_data[plugin]?.composer){
            composerSlug = data.ydtb_data[plugin].composer;
        } else {
            blurred = true;
            composerSlug = `author/${plugin.split("/")[0]}`;
        }

        transformedData[plugin] = {};
        transformedData[plugin].id = count;
        transformedData[plugin].slug = plugin;
        transformedData[plugin].checked = data.ydtb_data[plugin]?.checked || false;
        transformedData[plugin].composerSlug = composerSlug;
        transformedData[plugin].name = data.plugins[plugin].Title;
        transformedData[plugin].current = data.plugins[plugin].Version;
        transformedData[plugin].available = data.transients.response[plugin]?.new_version || data.plugins[plugin].Version;
        transformedData[plugin].previous = data.last_pushed[plugin]?.version || "-";
        transformedData[plugin].date = data.last_pushed[plugin]?.date || "-";
        transformedData[plugin].pushAvailable = plugin in data.transients.response;
        transformedData[plugin].downloadPlugin = false;
        transformedData[plugin].blurred = blurred;
        count++;
    }

    return Object.values(transformedData);
}
export default transform;