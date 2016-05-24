module.exports = function(grunt) {

    grunt.initConfig({
        compress: {
            main: {
                options: {
                    archive: 'mdimagemagick.zip'
                },
                files: [
                    {src: ['controllers/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: ['classes/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: ['docs/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: ['override/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: ['logs/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: ['vendor/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: ['translations/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: ['upgrade/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: ['optionaloverride/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: ['oldoverride/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: ['lib/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: ['defaultoverride/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: ['views/**'], dest: 'mdimagemagick/', filter: 'isFile'},
                    {src: 'config.xml', dest: 'mdimagemagick/'},
                    {src: 'index.php', dest: 'mdimagemagick/'},
                    {src: 'mdimagemagick.php', dest: 'mdimagemagick/'},
                    {src: 'logo.png', dest: 'mdimagemagick/'},
                    {src: 'logo.gif', dest: 'mdimagemagick/'}
                ]
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-compress');

    grunt.registerTask('default', ['compress']);
};