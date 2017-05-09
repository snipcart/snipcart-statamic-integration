var marked = require('marked');

marked.setOptions({
    gfm: true,
    tables: true
});

module.exports = function(value) {
    return marked(value);
};
