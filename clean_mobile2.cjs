const fs = require('fs');
const path = require('path');

const targetFiles = [
    "resources/js/Pages/Cart/Index.vue",
    "resources/js/Pages/Catalog/Index.vue",
    "resources/js/Pages/Products/Show.vue"
];

const basePath = "c:/xampp/htdocs/hiko-app";

targetFiles.forEach(file => {
    const fullPath = path.join(basePath, file);
    if (!fs.existsSync(fullPath)) {
        console.log("Missing:", fullPath);
        return;
    }
    let content = fs.readFileSync(fullPath, 'utf8');

    // 2. Remove the Mobile View block
    // We match from <!-- ========================================================= -->
    //         <!-- [MOBILE VIEW
    // down to either </DefaultLayout> or </template>
    
    // Instead of regex, let's just find the index of "<div class=\"block lg:hidden"
    // or similar and cut it. Actually let's search for "<!-- [MOBILE VIEW"
    const mobileStartMatch = content.indexOf("<!-- [MOBILE VIEW");
    
    if (mobileStartMatch !== -1) {
        // Find the start of the comment block above it if we want to be clean, but let's just cut from here
        // Wait, the match might be preceded by spaces and another <!-- ====
        // Let's just find the index of the enclosing <div> for mobile:
        const mobileDivMatch = content.match(/<div[^>]*class="[^"]*block\s+lg:hidden[^"]*"[^>]*>/);
        if(mobileDivMatch) {
             let startIndex = content.lastIndexOf("<!--", mobileDivMatch.index);
             // go further back if there are multiple lines of comments
             while(content.lastIndexOf("<!--", startIndex - 1) > startIndex - 200) {
                 const prev = content.lastIndexOf("<!--", startIndex - 1);
                 if (prev !== -1 && startIndex - prev < 150) {
                     startIndex = prev;
                 } else {
                     break;
                 }
             }

             const layoutEndMatch = content.indexOf("</DefaultLayout>", startIndex);
             const appLayoutEndMatch = content.indexOf("</AppLayout>", startIndex);
             const templateEndMatch = content.indexOf("</template>", startIndex);
             
             let endIndex = -1;
             if (layoutEndMatch !== -1) endIndex = layoutEndMatch;
             else if (appLayoutEndMatch !== -1) endIndex = appLayoutEndMatch;
             else if (templateEndMatch !== -1) endIndex = templateEndMatch;

             if (endIndex !== -1) {
                 const beforeMobile = content.substring(0, startIndex);
                 const afterMobile = content.substring(endIndex);
                 content = beforeMobile + "\n" + afterMobile;
                 console.log("Successfully cut mobile view in:", file);
             }
        }
    } else {
        console.log("Could not find <!-- [MOBILE VIEW in", file);
    }

    fs.writeFileSync(fullPath, content);
});
