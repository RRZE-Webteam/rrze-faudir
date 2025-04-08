import {__} from "@wordpress/i18n";
import {CheckboxControl} from "@wordpress/components";

interface PersonSelectorProps {
  isLoadingPosts: boolean;
  posts: any[];
  selectedPosts: number[];
  togglePostSelection: (postId: number) => void;
}

export default function PersonSelector({isLoadingPosts, posts, selectedPosts, togglePostSelection}: PersonSelectorProps) {
  return (
    <>
      <h4>{__('Select Persons', 'rrze-faudir')}</h4>

      {isLoadingPosts ? (
        <p>
          {__('Loading persons...', 'rrze-faudir')}
        </p>
      ) : posts.length > 0 ? (
        <>
          {posts.map((post) => {
            return (
              <CheckboxControl
                key={post.id}
                label={post.title.rendered}
                checked={
                  Array.isArray(
                    selectedPosts
                  ) &&
                  selectedPosts.includes(
                    post.id
                  )
                }
                onChange={() =>
                  togglePostSelection(
                    post.id
                  )
                }
              />
            );
          })}
        </>
      ) : (
        <p>
          {__('No posts available.', 'rrze-faudir')}
        </p>
      )}
    </>
  );
}