const path = require(`path`);

const _ = require("lodash");
const { createFilePath } = require(`gatsby-source-filesystem`);

exports.onCreateNode = ({ node, getNode, actions }, pluginOptions) => {
  const { createNodeField } = actions;

  if (node.internal.type === `MarkdownRemark`) {
    const slug = createFilePath({ node, getNode, basePath: `pages` });
    const parent = getNode(node.parent);
    const idName = _.kebabCase(node.frontmatter.title || parent.name);

    createNodeField({
      node,
      name: `slug`,
      value: slug
    });

    createNodeField({
      node,
      name: `idName`,
      value: idName
    });

    // save the file's directory so it can be used by the Template
    // component to group data in a GraphQL query
    createNodeField({
      node,
      name: `parentRelativeDirectory`,
      value: parent.relativeDirectory
    });

    // set a version field on pages so they can be queried
    // appropriately in the Template component
    let version = pluginOptions.currentVersion;
    if (parent.gitRemote___NODE) {
      const { sourceInstanceName } = getNode(parent.gitRemote___NODE);
      version = sourceInstanceName;
    }

    createNodeField({
      node,
      name: `version`,
      value: version
    });
  }
};

exports.createPages = async ({ actions, graphql }, pluginOptions) => {
  actions.createPage({
    path: `/`,
    component: path.resolve(`./src/components/template.js`)
  });
};
