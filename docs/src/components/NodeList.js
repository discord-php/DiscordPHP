import React from 'react';
import apiStyles from "./api.module.css";

export default class NodeList extends React.Component {
  constructor(props) {
    super(props);

    this.titleNode = this.props.nodes.shift();
  }

  isActive() {
    return this.props.getCurrentTitle() === this.titleNode.fields.idName;
  }

  onTitleClick() {
    this.props.onTitleClick(this.titleNode);
  }

  createMenuItemName(node) {
    return `${this.titleNode.fields.idName}/${node.fields.idName}`;
  }

  render() {
    return (
      <li>
        <a
          href={`#${this.titleNode.fields.idName}`}
          onClick={this.onTitleClick.bind(this)}
          class={this.isActive() ? apiStyles.activelink : undefined}
        >
          {this.titleNode.frontmatter.title}
        </a>
        <ol className={this.isActive() ? undefined : apiStyles.subhidden}>
          {this.props.nodes.map(node => (
            <li key={node.id}>
              <a href={`#${node.fields.idName}`}>
                {node.frontmatter.title}
              </a>
            </li>
          ))}
        </ol>
      </li>
    );
  }
}
