import React, { Fragment } from "react";
import { Helmet } from "react-helmet";

import { graphql } from "gatsby";

import IndexPage from "./index-page";

export default ({ data }) => {
  return (
    <Fragment>
      <Helmet>
        <meta charset="utf-8" />
        <title>DiscordPHP</title>
      </Helmet>
      <IndexPage data={data} />
    </Fragment>
  );
};

export const query = graphql`
  query {
    # staticMethods are pages sourced from this repo
    staticMethods: allMarkdownRemark(
      sort: { fields: fields___slug }
    ) {
      edges {
        node {
          id
          frontmatter {
            title
          }
          html
          fields {
            idName
            slug
          }
        }
      }
    }
  }
`;
