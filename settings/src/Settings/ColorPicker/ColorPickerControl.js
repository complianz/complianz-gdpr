import { memo } from "@wordpress/element";
import './ColorPicker.scss';
import ColorPickerElement from './ColorPickerElement';

/**
 * A wrapper component that renders multiple color picker elements.
 *
 * The memo HOC is used to prevent unnecessary re-renders when props
 * haven’t changed. This component doesn’t manage any local state.
 */
const ColorPickerControl = (props) => {
  
  return (
      <div className="cmplz-color-picker-control">
          {props.field.fields.map((item, i) => (
            <ColorPickerElement key={i} item={item} field={props.field} />
          ))}
      </div>
    );
  };

export default memo(ColorPickerControl);
