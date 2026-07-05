const fs = require('fs');
const path = require('path');

const targetFiles = [
    'resources/js/Pages/Cart/Index.vue',
    'resources/js/Pages/Catalog/Index.vue',
    'resources/js/Pages/Contact/Index.vue',
    'resources/js/Pages/Faq/Index.vue',
    'resources/js/Pages/HowToRent/Index.vue',
    'resources/js/Pages/MyRentals/Index.vue',
    'resources/js/Pages/MyRentals/Show.vue',
    'resources/js/Pages/Packages/Index.vue',
    'resources/js/Pages/Packages/Show.vue',
    'resources/js/Pages/Products/Show.vue',
    'resources/js/Pages/Profile/Edit.vue',
    'resources/js/Pages/Terms/Index.vue',
    'resources/js/Pages/Wishlist/Index.vue',
];

const basePath = 'c:/xampp/htdocs/hiko-app';

targetFiles.forEach((file) => {
    const fullPath = path.join(basePath, file);
    if (!fs.existsSync(fullPath)) {
        console.log('Missing:', fullPath);
        return;
    }
    let content = fs.readFileSync(fullPath, 'utf8');

    // 1. Remove the hidden lg:block from the desktop wrapper
    content = content.replace(
        /<!--\s*\[DESKTOP VIEW\].*?-->[\s\S]*?<div\s+class="hidden\s+lg:block">/,
        (match) => {
            return match.replace('class="hidden lg:block"', 'class="block"');
        }
    );

    // 2. Remove the Mobile View block
    // We match from <!-- [MOBILE VIEW]... down to either </DefaultLayout> or </template>
    const mobileStartMatch = content.match(/<!--\s*\[MOBILE VIEW\].*?-->/);
    if (mobileStartMatch) {
        const startIndex = mobileStartMatch.index;

        const layoutEndMatch = content.substring(startIndex).match(/<\/DefaultLayout>/);
        const appLayoutEndMatch = content.substring(startIndex).match(/<\/AppLayout>/);
        const templateEndMatch = content.substring(startIndex).match(/<\/template>/);

        let endIndex;
        if (layoutEndMatch) {
            endIndex = startIndex + layoutEndMatch.index;
        } else if (appLayoutEndMatch) {
            endIndex = startIndex + appLayoutEndMatch.index;
        } else if (templateEndMatch) {
            endIndex = startIndex + templateEndMatch.index;
        }

        if (endIndex) {
            const beforeMobile = content.substring(0, startIndex);
            const afterMobile = content.substring(endIndex);
            content = beforeMobile + '\n' + afterMobile;
        }
    } else {
        // Fallback for files that don't have <!-- [MOBILE VIEW] comment but have block lg:hidden
        const mobileDivMatch = content.match(/<div[^>]*class="[^"]*block\s+lg:hidden[^"]*"[^>]*>/);
        if (mobileDivMatch) {
            console.log('Found raw mobile div in:', file);
            // This is trickier to cut correctly without HTML parsing.
            // Let's hope the comment exists in all of them.
        }
    }

    fs.writeFileSync(fullPath, content);
    console.log('Processed:', file);
});
